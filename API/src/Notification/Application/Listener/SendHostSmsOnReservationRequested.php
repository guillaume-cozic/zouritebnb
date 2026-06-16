<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Sms\HostSms;
use App\Notification\Application\UseCase\QueueSms;
use App\Notification\Domain\Command\QueueSmsCommand;
use App\Shared\Domain\Event\ReservationRequested;

/**
 * Queues an SMS (into the outbox) for each host of the team who has a phone number, alerting
 * them that a new booking request awaits their decision. Hosts without a phone are skipped.
 */
final readonly class SendHostSmsOnReservationRequested
{
    public function __construct(
        private HostReservationEmailContextResolver $resolver,
        private HostSms $sms,
        private QueueSms $queueSms,
    ) {
    }

    public function __invoke(ReservationRequested $event): void
    {
        $context = $this->resolver->resolve($event->reservationId);

        if (null === $context) {
            return;
        }

        $text = $this->sms->reservationRequested(
            $context->guestName,
            $context->accommodationTitle,
            $context->checkIn,
            $context->checkOut,
        );

        foreach ($context->hostContacts as $host) {
            if (!$host->hasPhone()) {
                continue;
            }

            $this->queueSms->handle(new QueueSmsCommand($host->phoneNumber, $text));
        }
    }
}
