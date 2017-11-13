<?php

namespace Tests\AppBundle\Mailer\Message;

use AppBundle\Entity\Invite;
use AppBundle\Mailer\Message\InvitationMessage;
use AppBundle\Mailer\Message\MessageRecipient;
use PHPUnit\Framework\TestCase;

class InvitationMessageTest extends TestCase
{
    public function testCreateInvitationMessageFromInvite()
    {
        $message = InvitationMessage::createFromInvite(Invite::create(
            'Paul',
            'Auffray',
            'jerome.picon@gmail.tld',
            'Vous êtes invités par Paul Auffray !',
            '192.168.12.25'
        ));

        $this->assertInstanceOf(InvitationMessage::class, $message);
        $this->assertSame('108243', $message->getTemplate());
        $this->assertSame('Paul Auffray vous invite à rejoindre En Marche.', $message->getSubject());
        $this->assertCount(3, $message->getVars());
        $this->assertSame(
            [
                'sender_firstname' => 'Paul',
                'sender_lastname' => 'Auffray',
                'target_message' => 'Vous êtes invités par Paul Auffray !',
            ],
            $message->getVars()
        );

        $recipient = $message->getRecipient('jerome.picon@gmail.tld');
        $this->assertInstanceOf(MessageRecipient::class, $recipient);
        $this->assertSame('jerome.picon@gmail.tld', $recipient->getEmailAddress());
        $this->assertNull($recipient->getFullName());
        $this->assertSame(
            [
                'sender_firstname' => 'Paul',
                'sender_lastname' => 'Auffray',
                'target_message' => 'Vous êtes invités par Paul Auffray !',
            ],
            $recipient->getVars()
        );
    }
}
