<?php

declare(strict_types=1);

namespace App\Donation\Application\Listener;

use App\Donation\Application\UseCase\RecordDonationStripeEvent;
use App\Donation\Domain\Command\RecordDonationStripeEventCommand;
use App\Shared\Domain\Event\StripePaymentIntentEventReceived;

/**
 * Reacts to Stripe payment intent webhook events and records their effect on the
 * matching Donation row. If the payment intent does not belong to a donation, the
 * use case silently no-ops.
 */
final readonly class RecordDonationOnStripePaymentIntentEvent
{
    public function __construct(private RecordDonationStripeEvent $recordDonationStripeEvent)
    {
    }

    public function __invoke(StripePaymentIntentEventReceived $event): void
    {
        $this->recordDonationStripeEvent->handle(new RecordDonationStripeEventCommand(
            eventType: $event->eventType,
            paymentIntentId: $event->paymentIntentId,
        ));
    }
}
