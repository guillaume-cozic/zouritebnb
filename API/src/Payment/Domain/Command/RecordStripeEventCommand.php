<?php

declare(strict_types=1);

namespace App\Payment\Domain\Command;

final readonly class RecordStripeEventCommand
{
    public function __construct(
        public string $eventType,
        public string $paymentIntentId,
    ) {
    }
}
