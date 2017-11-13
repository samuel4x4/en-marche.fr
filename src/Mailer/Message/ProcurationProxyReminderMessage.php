<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\ProcurationRequest;
use AppBundle\Utils\PhoneNumberFormatter;
use Ramsey\Uuid\Uuid;

final class ProcurationProxyReminderMessage extends Message
{
    public static function create(ProcurationRequest $request, string $infosUrl): self
    {
        $message = new self(
            Uuid::uuid4(),
            '133881',
            $request->getEmailAddress(),
            null,
            'RAPPEL : votre procuration',
            self::createRecipientVariables($request, $infosUrl)
        );

        $message->setSenderName('Procuration En Marche !');

        return $message;
    }

    public static function createRecipientVariables(ProcurationRequest $request, string $infosUrl)
    {
        $proxy = $request->getFoundProxy();

        return [
            'target_firstname' => self::escape($request->getFirstNames()),
            'info_link' => $infosUrl,
            'elections' => implode(', ', $request->getElections()),
            'voter_first_name' => self::escape($proxy->getFirstNames()),
            'voter_last_name' => self::escape($proxy->getLastName()),
            'voter_phone' => PhoneNumberFormatter::format($proxy->getPhone()),
            'mandant_first_name' => self::escape($request->getFirstNames()),
            'mandant_last_name' => self::escape($request->getLastName()),
            'mandant_phone' => PhoneNumberFormatter::format($request->getPhone()),
        ];
    }
}
