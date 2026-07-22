<?php

declare(strict_types=1);

namespace App\Donation\Domain\Port;

final readonly class GatewayPayment
{
    public function __construct(
        public string $paymentIntentId,
        public string $clientSecret,
    ) {
    }
}
