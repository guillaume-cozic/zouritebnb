<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\HostEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\ReservationCancelled;

/**
 * Notifies the host(s) that a reservation on their accommodation was cancelled.
 */
final readonly class SendHostCancellationEmailOnReservationCancelled
{
    public function __construct(
        private HostReservationEmailContextResolver $resolver,
        private HostEmails $emails,
        private QueueEmail $queueEmail,
    ) {
    }

    public function __invoke(ReservationCancelled $event): void
    {
        $context = $this->resolver->resolve($event->reservationId);

        if (null === $context) {
            return;
        }

        foreach ($context->hostContacts as $host) {
            $view = $this->emails->reservationCancelled(
                $host->greetingName(),
                $context->guestName,
                $context->accommodationTitle,
                $context->checkIn,
                $context->checkOut,
            );

            $this->queueEmail->handle(new QueueEmailCommand(
                recipientEmail: $host->email,
                recipientName: $host->firstName,
                subject: $view->subject,
                template: $view->template,
                variables: $view->variables,
            ));
        }
    }
}
