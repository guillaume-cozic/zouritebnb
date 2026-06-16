<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\TravelerEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\ReservationRefused;

final readonly class SendRefusalEmailOnReservationRefused
{
    public function __construct(
        private ReservationEmailContextResolver $resolver,
        private TravelerEmails $emails,
        private QueueEmail $queueEmail,
    ) {
    }

    public function __invoke(ReservationRefused $event): void
    {
        $context = $this->resolver->resolve($event->reservationId);

        if (null === $context) {
            return;
        }

        // An automatic refusal means the 24h window elapsed without a host decision.
        $view = $event->isAutomatic
            ? $this->emails->reservationExpired($context->guest->greetingName(), $context->accommodationTitle)
            : $this->emails->reservationRefused($context->guest->greetingName(), $context->accommodationTitle);

        $this->queueEmail->handle(new QueueEmailCommand(
            recipientEmail: $context->guest->email,
            recipientName: $context->guest->firstName,
            subject: $view->subject,
            template: $view->template,
            variables: $view->variables,
        ));
    }
}
