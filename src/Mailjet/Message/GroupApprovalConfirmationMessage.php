<?php

namespace AppBundle\Mailjet\Message;

use AppBundle\Entity\Adherent;
use Ramsey\Uuid\Uuid;

final class GroupApprovalConfirmationMessage extends MailjetMessage
{
    public static function create(Adherent $administrator): self
    {
        $message = new self(
            Uuid::uuid4(),
            '244444',
            $administrator->getEmailAddress(),
            self::fixMailjetParsing($administrator->getFullName()),
            'Votre équipe MOOC est validée, à vous de jouer'
        );

        $message->setVar('target_firstname', self::escape($administrator->getFirstName()));

        return $message;
    }
}
