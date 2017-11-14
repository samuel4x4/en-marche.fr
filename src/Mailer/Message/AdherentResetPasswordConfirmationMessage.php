<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\Adherent;
use Ramsey\Uuid\Uuid;

final class AdherentResetPasswordConfirmationMessage extends Message
{
    public static function createFromAdherent(Adherent $adherent): self
    {
        return new self(
            Uuid::uuid4(),
            $adherent->getEmailAddress(),
            $adherent->getFullName(),
            ['target_firstname' => self::escape($adherent->getFirstName())]
        );
    }
}
