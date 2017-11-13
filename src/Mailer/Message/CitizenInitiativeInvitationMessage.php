<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\CitizenInitiative;
use AppBundle\Entity\EventInvite;
use Ramsey\Uuid\Uuid;

final class CitizenInitiativeInvitationMessage extends Message
{
    public static function createFromInvite(EventInvite $invite, CitizenInitiative $initiative, string $eventUrl): self
    {
        $message = new self(
            Uuid::uuid4(),
            '132747',
            $invite->getEmail(),
            self::escape($invite->getFullName()),
            $invite->getFullName().' vous invite à un événement En Marche !',
            [
                'sender_firstname' => self::escape($invite->getFirstName()),
                'sender_message' => self::escape($invite->getMessage()),
                'event_name' => self::escape($initiative->getName()),
                'event_slug' => $eventUrl,
            ]
        );

        $message->setReplyTo($invite->getEmail());

        foreach ($invite->getGuests() as $guest) {
            $message->addCC($guest);
        }

        return $message;
    }
}
