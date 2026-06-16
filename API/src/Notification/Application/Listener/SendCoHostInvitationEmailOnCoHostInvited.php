<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\HostEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\CoHostInvited;

/**
 * Sends the invitation email to a freshly invited co-host. The recipient address is carried
 * by the event itself (the invitee may not have an account yet).
 */
final readonly class SendCoHostInvitationEmailOnCoHostInvited
{
    public function __construct(
        private HostEmails $emails,
        private QueueEmail $queueEmail,
    ) {
    }

    public function __invoke(CoHostInvited $event): void
    {
        $view = $this->emails->coHostInvitation($event->email);

        $this->queueEmail->handle(new QueueEmailCommand(
            recipientEmail: $event->email,
            recipientName: null,
            subject: $view->subject,
            template: $view->template,
            variables: $view->variables,
        ));
    }
}
