<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Integration event published when a Stripe payment_intent.* webhook event is received.
 * It lives in Shared because several contexts (Payment, Donation) react to the same
 * Stripe payment intent lifecycle facts.
 */
final readonly class StripePaymentIntentEventReceived implements DomainEvent
{
    public function __construct(
        public string $eventType,
        public string $paymentIntentId,
    ) {
    }
}
