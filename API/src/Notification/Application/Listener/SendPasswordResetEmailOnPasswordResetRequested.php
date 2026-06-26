<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\TravelerEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\PasswordResetRequested;
use App\Shared\Domain\Port\UserContactProvider;

final readonly class SendPasswordResetEmailOnPasswordResetRequested
{
    public function __construct(
        private UserContactProvider $contacts,
        private TravelerEmails $emails,
        private QueueEmail $queueEmail,
        private string $frontendUrl,
    ) {
    }

    public function __invoke(PasswordResetRequested $event): void
    {
        $contact = $this->contacts->contactOf($event->userId);

        if (null === $contact) {
            return;
        }

        $resetUrl = \sprintf('%s/reset-password?token=%s', rtrim($this->frontendUrl, '/'), rawurlencode($event->token));

        $view = $this->emails->passwordReset($contact->greetingName(), $resetUrl);

        $this->queueEmail->handle(new QueueEmailCommand(
            recipientEmail: $contact->email,
            recipientName: $contact->firstName,
            subject: $view->subject,
            template: $view->template,
            variables: $view->variables,
        ));
    }
}
