<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\CitizenInitiative;
use Ramsey\Uuid\Uuid;

final class CitizenInitiativeActivitySubscriptionMessage extends Message
{
    /**
     * Creates a new message instance for a list of recipients.
     *
     * @param Adherent[]        $recipients
     * @param Adherent          $organizer
     * @param CitizenInitiative $citizenInitiative
     * @param string            $citizenInitiativeLink
     * @param \Closure          $recipientVarsGenerator
     *
     * @return CitizenInitiativeActivitySubscriptionMessage
     */
    public static function create(
        array $recipients,
        Adherent $organizer,
        CitizenInitiative $citizenInitiative,
        string $citizenInitiativeLink,
        \Closure $recipientVarsGenerator
    ): self {
        if (!$recipients) {
            throw new \InvalidArgumentException('At least one Adherent recipients is required.');
        }

        $recipient = array_shift($recipients);
        if (!$recipient instanceof Adherent) {
            throw new \RuntimeException('First recipient must be an Adherent instance.');
        }

        $message = new static(
            Uuid::uuid4(),
            $recipient->getEmailAddress(),
            $recipient->getFullName(),
            self::getTemplateVars(
                $organizer->getFirstName(),
                $organizer->getLastName(),
                $citizenInitiative->getName(),
                self::formatDate($citizenInitiative->getBeginAt(), 'EEEE d MMMM y'),
                sprintf(
                    '%sh%s',
                    self::formatDate($citizenInitiative->getBeginAt(), 'HH'),
                    self::formatDate($citizenInitiative->getBeginAt(), 'mm')
                ),
                $citizenInitiative->getInlineFormattedAddress(),
                $citizenInitiativeLink
            ),
            $recipientVarsGenerator($recipient),
            $organizer->getEmailAddress()
        );

        /* @var Adherent[] $recipients */
        foreach ($recipients as $recipient) {
            $message->addRecipient(
                $recipient->getEmailAddress(),
                $recipient->getFullName(),
                $recipientVarsGenerator($recipient)
            );
        }

        return $message;
    }

    public static function getTemplateVars(
        string $organizerFirstName,
        string $organizerLastName,
        string $citizenInitiativeName,
        string $citizenInitiativeDate,
        string $citizenInitiativeHour,
        string $citizenInitiativeAddress,
        string $citizenInitiativeLink
    ): array {
        return [
            // Global common variables
            'IC_organizer_firstname' => self::escape($organizerFirstName),
            'IC_organizer_lastname' => self::escape($organizerLastName),
            'IC_name' => self::escape($citizenInitiativeName),
            'IC_date' => $citizenInitiativeDate,
            'IC_hour' => $citizenInitiativeHour,
            'IC_address' => self::escape($citizenInitiativeAddress),
            'IC_link' => $citizenInitiativeLink,
        ];
    }

    public static function getRecipientVars(string $firstName): array
    {
        return [
            'prenom' => self::escape($firstName),
        ];
    }
}
