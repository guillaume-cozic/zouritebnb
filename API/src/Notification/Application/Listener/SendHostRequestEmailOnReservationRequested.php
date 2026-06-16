<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\HostEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\ReservationRequested;

/**
 * Notifies the host(s) that a new booking request awaits their decision (24h window).
 */
final readonly class SendHostRequestEmailOnReservationRequested
{
    public function __construct(
        private HostReservationEmailContextResolver $resolver,
        private HostEmails $emails,
        private QueueEmail $queueEmail,
    ) {
    }

    public function __invoke(ReservationRequested $event): void
    {
        $context = $this->resolver->resolve($event->reservationId);

        if (null === $context) {
            return;
        }

        foreach ($context->hostContacts as $host) {
            $view = $this->emails->reservationRequested(
                $host->greetingName(),
                $context->guestName,
                $context->accommodationTitle,
                $context->city,
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
