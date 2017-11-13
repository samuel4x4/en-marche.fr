<?php

namespace AppBundle\Consumer;

use AppBundle\Entity\Adherent;
use AppBundle\Mailer\MailerService;
use AppBundle\Mailer\Message\ReferentMessage as Message;
use AppBundle\Referent\ReferentMessage;
use AppBundle\Repository\AdherentRepository;
use AppBundle\Repository\Projection\ReferentManagedUserRepository;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ReferentMessageDispatcherConsumer extends AbstractConsumer
{
    /**
     * @var MailerService
     */
    private $mailer;
    /**
     * @var AdherentRepository
     */
    private $adherentRepository;
    /**
     * @var ReferentManagedUserRepository
     */
    private $referentManagedUserRepository;

    protected function configureDataConstraints(): array
    {
        return [
            'referent_uuid' => [new Assert\NotBlank()],
            'filter' => [new Assert\NotBlank()],
            'subject' => [new Assert\NotBlank()],
            'content' => [new Assert\NotBlank()],
        ];
    }

    public function doExecute(array $data): int
    {
        try {
            if (!$referent = $this->getAdherentRepository()->findByUuid($data['referent_uuid'])) {
                $this->getLogger()->error('Referent not found', $data);
                $this->writeln($data['referent_uuid'], 'Referent not found, rejecting');

                return ConsumerInterface::MSG_ACK;
            }

            $message = $this->createReferentMessage($referent, $data);
            $this->writeln($data['referent_uuid'], 'Dispatching message from '.$referent->getEmailAddress());

            /** @var IterableResult $results */
            $results = $this->getReferentManagedUserRepository()->createDispatcherIterator($referent, $message->getFilter());

            $i = 0;
            $count = 0;
            $chunk = [];

            foreach ($results as $result) {
                ++$i;
                ++$count;
                $chunk[] = $result[0];

                if (MailerService::PAYLOAD_MAXSIZE === $i) {
                    $this->getMailer()->sendMessage($this->createMailerReferentMessage($message, $chunk));
                    $this->writeln($data['referent_uuid'], 'Message from '.$referent->getEmailAddress().' dispatched ('.$count.')');

                    $i = 0;
                    $chunk = [];

                    $this->getManager()->clear();
                }
            }

            if (!empty($chunk)) {
                $this->getMailer()->sendMessage($this->createMailerReferentMessage($message, $chunk));
                $this->writeln($data['referent_uuid'], 'Message from '.$referent->getEmailAddress().' dispatched ('.$count.')');
            }

            return ConsumerInterface::MSG_ACK;
        } catch (\Exception $error) {
            $this->getLogger()->error('Consumer failed', ['exception' => $error]);

            throw $error;
        }
    }

    public function setMailer(MailerService $mailer): void
    {
        $this->mailer = $mailer;
    }

    public function getMailer(): MailerService
    {
        return $this->mailer;
    }

    public function setReferentManagedUserRepository(ReferentManagedUserRepository $referentManagedUserRepository): void
    {
        $this->referentManagedUserRepository = $referentManagedUserRepository;
    }

    public function getReferentManagedUserRepository(): ReferentManagedUserRepository
    {
        return $this->referentManagedUserRepository;
    }

    public function setAdherentRepository(AdherentRepository $adherentRepository): void
    {
        $this->adherentRepository = $adherentRepository;
    }

    public function getAdherentRepository(): AdherentRepository
    {
        return $this->adherentRepository;
    }

    public function createReferentMessage(Adherent $referent, array $data): ReferentMessage
    {
        return ReferentMessage::createFromArray($referent, $data);
    }

    public function createMailerReferentMessage(ReferentMessage $message, array $recipients): Message
    {
        return Message::createFromModel($message, $recipients);
    }
}
