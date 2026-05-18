<?php

declare(strict_types=1);

namespace App\Payment\Domain\Port;

final readonly class GatewayAuthorization
{
    public function __construct(
        public string $paymentIntentId,
        public string $clientSecret,
    ) {
    }
}
