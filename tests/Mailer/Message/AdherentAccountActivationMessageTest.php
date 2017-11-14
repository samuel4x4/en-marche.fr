<?php

namespace Tests\AppBundle\Mailer\Message;

use AppBundle\Entity\Adherent;
use AppBundle\Mailer\Message\AdherentAccountActivationMessage;
use AppBundle\Mailer\Message\Message;
use AppBundle\Mailer\Message\MessageRecipient;
use PHPUnit\Framework\TestCase;

class AdherentAccountActivationMessageTest extends TestCase
{
    private const CONFIRMATION_URL = 'https://enmarche.dev/activation';

    public function testCreateAdherentAccountActivationMessage()
    {
        $adherent = $this->createMock(Adherent::class);
        $adherent->expects($this->once())->method('getEmailAddress')->willReturn('jerome@example.com');
        $adherent->expects($this->once())->method('getFullName')->willReturn('Jérôme Pichoud');
        $adherent->expects($this->once())->method('getFirstName')->willReturn('Jérôme');

        $message = AdherentAccountActivationMessage::createFromAdherent($adherent, self::CONFIRMATION_URL);

        $this->assertInstanceOf(AdherentAccountActivationMessage::class, $message);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEmpty($message->getVars());
        $this->assertCount(1, $message->getRecipients());

        $recipient = $message->getRecipient(0);
        $this->assertInstanceOf(MessageRecipient::class, $recipient);
        $this->assertSame('jerome@example.com', $recipient->getEmailAddress());
        $this->assertSame('Jérôme Pichoud', $recipient->getFullName());
        $this->assertSame(
            [
                'target_firstname' => 'Jérôme',
                'confirmation_link' => self::CONFIRMATION_URL,
            ],
            $recipient->getVars()
        );
    }
}
