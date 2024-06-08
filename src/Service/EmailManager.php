<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

readonly class EmailManager
{
    public function __construct(
        private TransportInterface $mailer
        )
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMail (Email $email): void
    {
    $this->mailer->send($email);
    }
}
