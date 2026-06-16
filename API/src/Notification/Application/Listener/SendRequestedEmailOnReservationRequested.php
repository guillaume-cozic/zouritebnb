<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\TravelerEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\ReservationRequested;

final readonly class SendRequestedEmailOnReservationRequested
{
    public function __construct(
        private ReservationEmailContextResolver $resolver,
        private TravelerEmails $emails,
        private QueueEmail $queueEmail,
    ) {
    }

    public function __invoke(ReservationRequested $event): void
    {
        $context = $this->resolver->resolve($event->reservationId);

        if (null === $context) {
            return;
        }

        $rendered = $this->emails->reservationRequested(
            $context->guest->greetingName(),
            $context->accommodationTitle,
            $context->city,
            $context->checkIn,
            $context->checkOut,
        );

        $this->queueEmail->handle(new QueueEmailCommand(
            recipientEmail: $context->guest->email,
            recipientName: $context->guest->firstName,
            subject: $rendered->subject,
            htmlBody: $rendered->htmlBody,
        ));
    }
}
