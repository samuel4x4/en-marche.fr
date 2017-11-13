<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\ProcurationProxy;
use AppBundle\Entity\ProcurationRequest;
use AppBundle\Utils\PhoneNumberFormatter;
use Ramsey\Uuid\Uuid;

final class ProcurationProxyFoundMessage extends Message
{
    public static function create(
        Adherent $procurationManager,
        ProcurationRequest $request,
        ProcurationProxy $proxy,
        string $infosUrl
    ): self {
        $message = new self(
            Uuid::uuid4(),
            '120187',
            $request->getEmailAddress(),
            null,
            'Votre procuration',
            [
                'target_firstname' => self::escape($request->getFirstNames()),
                'info_link' => $infosUrl,
                'elections' => implode(', ', $request->getElections()),
                'voter_first_name' => self::escape($proxy->getFirstNames()),
                'voter_last_name' => self::escape($proxy->getLastName()),
                'voter_phone' => PhoneNumberFormatter::format($proxy->getPhone()),
                'mandant_first_name' => self::escape($request->getFirstNames()),
                'mandant_last_name' => self::escape($request->getLastName()),
                'mandant_phone' => PhoneNumberFormatter::format($request->getPhone()),
            ]
        );

        $message->setSenderName('Procuration En Marche !');
        $message->addCC($procurationManager->getEmailAddress());
        $message->addCC($proxy->getEmailAddress());
        $message->setReplyTo($proxy->getEmailAddress());

        return $message;
    }
}
