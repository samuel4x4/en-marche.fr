<?php

namespace Tests\AppBundle\Test\Mailer;

use AppBundle\Mailer\EmailClientInterface;
use Psr\Log\LoggerInterface;

class NullEmailClient implements EmailClientInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function sendEmail(string $email): string
    {
        if ($this->logger) {
            $this->logger->info('[mailer] sending email with Mailer.', ['email' => $email]);
        }

        return 'Delivered using NULL client';
    }
}
