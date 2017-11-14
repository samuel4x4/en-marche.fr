<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\TonMacronFriendInvitation;
use Ramsey\Uuid\Uuid;

final class TonMacronFriendMessage extends Message
{
    public static function createFromInvitation(TonMacronFriendInvitation $invitation): self
    {
        $message = new self(
            Uuid::uuid4(),
            $invitation->getFriendEmailAddress(),
            null,
            ['message' => $invitation->getMailBody()]
        );

        $message->setReplyTo($invitation->getAuthorEmailAddress());
        $message->setSenderName($invitation->getAuthorFirstName().' '.$invitation->getAuthorLastName());
        $message->addCC($invitation->getAuthorEmailAddress());

        return $message;
    }
}
