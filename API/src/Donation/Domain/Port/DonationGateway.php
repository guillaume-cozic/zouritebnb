<?php

declare(strict_types=1);

namespace App\Donation\Domain\Port;

interface DonationGateway
{
    /**
     * Creates an automatic-capture payment on the gateway (the donation is charged as
     * soon as the donor confirms, unlike reservations which use manual capture) and
     * returns the resulting intent identifier together with the client secret required
     * for the SCA confirmation step on the frontend.
     *
     * @param array<string, string|int|float|bool|null> $metadata
     */
    public function createPayment(
        int $amountCents,
        string $currency,
        string $description,
        array $metadata,
    ): GatewayPayment;
}
