<?php

declare(strict_types=1);

namespace App\Donation\Domain\Command;

final readonly class RecordDonationStripeEventCommand
{
    public function __construct(
        public string $eventType,
        public string $paymentIntentId,
    ) {
    }
}
