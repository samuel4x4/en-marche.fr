<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\Adherent;
use Ramsey\Uuid\Uuid;

class CommitteeCreationConfirmationMessage extends Message
{
    public static function create(Adherent $adherent, string $city): self
    {
        $message = new self(
            Uuid::uuid4(),
            $adherent->getEmailAddress(),
            $adherent->getFullName()
        );

        $message->setVar('target_firstname', self::escape($adherent->getFirstName()));
        $message->setVar('committee_city', $city);

        return $message;
    }
}
