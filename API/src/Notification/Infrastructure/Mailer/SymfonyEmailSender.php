<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Mailer;

use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Exception\EmailDeliveryException;
use App\Notification\Domain\Port\EmailSender;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class SymfonyEmailSender implements EmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
        private string $fromName,
    ) {
    }

    public function send(OutboxEmail $email): void
    {
        $recipient = null !== $email->getRecipientName()
            ? new Address($email->getRecipient()->toString(), $email->getRecipientName())
            : new Address($email->getRecipient()->toString());

        $message = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($recipient)
            ->subject($email->getSubject())
            ->html($email->getHtmlBody());

        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $exception) {
            throw EmailDeliveryException::because($exception->getMessage(), $exception);
        }
    }
}
