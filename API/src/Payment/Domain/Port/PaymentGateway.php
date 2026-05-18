<?php

declare(strict_types=1);

namespace App\Payment\Domain\Port;

interface PaymentGateway
{
    /**
     * Creates a manual-capture authorization on the gateway and returns the resulting
     * intent identifier together with the client secret required for the SCA confirmation
     * step on the frontend.
     *
     * @param array<string, string|int|float|bool|null> $metadata
     */
    public function createAuthorization(
        int $amountCents,
        string $currency,
        string $description,
        array $metadata,
    ): GatewayAuthorization;

    public function capture(string $paymentIntentId): void;

    public function cancel(string $paymentIntentId): void;
}
