<?php

namespace App\Service;

use Symfony\Component\Mime\Email;

readonly class EmailManager
{
    public function __construct(
        private \Symfony\Component\Mailer\Transport\TransportInterface $mailer
        )
    {
    }

    public function sendMail (Email $email): void
    {
    $this->mailer->send($email);
    }
}