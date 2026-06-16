<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\TravelerEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\UserRegistered;
use App\Shared\Domain\Port\UserContactProvider;

final readonly class SendWelcomeEmailOnUserRegistered
{
    public function __construct(
        private UserContactProvider $contacts,
        private TravelerEmails $emails,
        private QueueEmail $queueEmail,
    ) {
    }

    public function __invoke(UserRegistered $event): void
    {
        $contact = $this->contacts->contactOf($event->userId);

        if (null === $contact) {
            return;
        }

        $view = $this->emails->welcome($contact->greetingName());

        $this->queueEmail->handle(new QueueEmailCommand(
            recipientEmail: $contact->email,
            recipientName: $contact->firstName,
            subject: $view->subject,
            template: $view->template,
            variables: $view->variables,
        ));
    }
}
