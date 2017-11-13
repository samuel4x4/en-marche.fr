<?php

namespace AppBundle\CitizenInitiative;

use AppBundle\Entity\CitizenInitiative;
use AppBundle\Events;
use AppBundle\Entity\Adherent;
use AppBundle\Mailer\MailerService;
use AppBundle\Mailer\Message\CitizenInitiativeActivitySubscriptionMessage;
use AppBundle\Mailer\Message\CitizenInitiativeNearSupervisorsMessage;
use AppBundle\Mailer\Message\CitizenInitiativeOrganizerValidationMessage;
use AppBundle\Mailer\Message\EventCancellationMessage;
use AppBundle\Mailer\Message\CitizenInitiativeAdherentsNearMessage;
use AppBundle\Membership\AdherentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CitizenInitiativeMessageNotifier implements EventSubscriberInterface
{
    private $mailer;
    private $adherentManager;
    private $urlGenerator;

    public function __construct(
        MailerService $mailer,
        AdherentManager $adherentManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->mailer = $mailer;
        $this->adherentManager = $adherentManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function onCitizenInitiativeCancelled(CitizenInitiativeCancelledEvent $event): void
    {
        if (!$event->getCitizenInitiative()->isCancelled()) {
            return;
        }

        $subscriptions = $this->adherentManager->findByEvent($event->getCitizenInitiative());

        if (count($subscriptions) > 0) {
            $chunks = array_chunk($subscriptions->toArray(), MailerService::PAYLOAD_MAXSIZE);

            foreach ($chunks as $chunk) {
                $this->mailer->sendMessage($this->createCancelMessage(
                    $chunk,
                    $event->getCitizenInitiative(),
                    $event->getAuthor()
                ));
            }
        }
    }

    private function createCancelMessage(array $registered, CitizenInitiative $initiative, Adherent $host): EventCancellationMessage
    {
        return EventCancellationMessage::create(
            $registered,
            $host,
            $initiative,
            $this->generateUrl('app_search_events'),
            function (Adherent $adherent) {
                return EventCancellationMessage::getRecipientVars($adherent->getFirstName());
            }
        );
    }

    public function onCitizenInitiativeValidated(CitizenInitiativeValidatedEvent $event): void
    {
        if (!$event->getCitizenInitiative()->isPublished()) {
            return;
        }

        $this->sendMessageToOrganizer($event);
        $this->sendMessageToNearByAdherentsWithSameInterests($event);
        $this->sendMessageToSupervisorsNearCitizenInitiative($event);
        $this->sendMessageToAdherentActivitySubscribers($event);
    }

    private function sendMessageToOrganizer(CitizenInitiativeValidatedEvent $event): void
    {
        $initiative = $event->getCitizenInitiative();

        $this->mailer->sendMessage($this->createMessageToOrganizer(
            $initiative->getOrganizer(),
            $initiative,
            $this->generateUrl('app_citizen_initiative_show', [
                'slug' => $initiative->getSlug(),
            ])
        ));
    }

    private function createMessageToOrganizer(Adherent $organizer, CitizenInitiative $initiative, string $link)
    {
        return CitizenInitiativeOrganizerValidationMessage::create(
            $organizer,
            $initiative,
            $link
        );
    }

    private function sendMessageToNearByAdherentsWithSameInterests(CitizenInitiativeValidatedEvent $event): void
    {
        $initiative = $event->getCitizenInitiative();

        $chunks = array_chunk(
            $this->adherentManager->findNearByCitizenInitiativeInterests($initiative)->toArray(),
            MailerService::PAYLOAD_MAXSIZE
        );

        foreach ($chunks as $chunk) {
            $this->mailer->sendMessage($this->createMessageToNearByAdherentsWithSameInterests($chunk, $initiative, $initiative->getOrganizer()));
        }
    }

    private function sendMessageToSupervisorsNearCitizenInitiative(CitizenInitiativeValidatedEvent $event): void
    {
        $initiative = $event->getCitizenInitiative();

        $chunks = array_chunk(
            $this->adherentManager->findSupervisorsNearCitizenInitiative($initiative)->toArray(),
            MailerService::PAYLOAD_MAXSIZE
        );

        foreach ($chunks as $chunk) {
            $this->mailer->sendMessage($this->createMessageToSupervisorsNearCitizenInitiative($chunk, $initiative, $initiative->getOrganizer()));
        }
    }

    private function createMessageToNearByAdherentsWithSameInterests(array $adherents, CitizenInitiative $citizenInitiative, Adherent $organizer): CitizenInitiativeAdherentsNearMessage
    {
        return CitizenInitiativeAdherentsNearMessage::create(
            $adherents,
            $organizer,
            $citizenInitiative,
            $this->generateUrl('app_citizen_initiative_show', [
                'slug' => $citizenInitiative->getSlug(),
            ]),
            function (Adherent $adherent) {
                return CitizenInitiativeAdherentsNearMessage::getRecipientVars($adherent->getFirstName());
            }
        );
    }

    private function createMessageToSupervisorsNearCitizenInitiative(array $adherents, CitizenInitiative $citizenInitiative, Adherent $organizer): CitizenInitiativeNearSupervisorsMessage
    {
        return CitizenInitiativeNearSupervisorsMessage::create(
            $adherents,
            $organizer,
            $citizenInitiative,
            $this->generateUrl('app_citizen_initiative_show', [
                'slug' => $citizenInitiative->getSlug(),
            ]),
            function (Adherent $adherent) {
                return CitizenInitiativeNearSupervisorsMessage::getRecipientVars($adherent->getFirstName());
            }
        );
    }

    private function sendMessageToAdherentActivitySubscribers(CitizenInitiativeValidatedEvent $event): void
    {
        $initiative = $event->getCitizenInitiative();

        $chunks = array_chunk(
            $this->adherentManager->findSubscribersToAdherentActivity($initiative->getOrganizer())->toArray(),
            MailerService::PAYLOAD_MAXSIZE
        );

        foreach ($chunks as $chunk) {
            $this->mailer->sendMessage($this->createMessageToAdherentActivitySubscribers($chunk, $initiative, $initiative->getOrganizer()));
        }
    }

    private function createMessageToAdherentActivitySubscribers(array $adherents, CitizenInitiative $citizenInitiative, Adherent $organizer): CitizenInitiativeActivitySubscriptionMessage
    {
        return CitizenInitiativeActivitySubscriptionMessage::create(
            $adherents,
            $organizer,
            $citizenInitiative,
            $this->generateUrl('app_citizen_initiative_show', [
                'slug' => $citizenInitiative->getSlug(),
            ]),
            function (Adherent $adherent) {
                return CitizenInitiativeActivitySubscriptionMessage::getRecipientVars($adherent->getFirstName());
            }
        );
    }

    private function generateUrl(string $route, array $params = []): string
    {
        return $this->urlGenerator->generate($route, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::CITIZEN_INITIATIVE_CANCELLED => ['onCitizenInitiativeCancelled', -128],
            Events::CITIZEN_INITIATIVE_VALIDATED => ['onCitizenInitiativeValidated', -128],
        ];
    }
}
