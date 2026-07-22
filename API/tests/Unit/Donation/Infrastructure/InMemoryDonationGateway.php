<?php

declare(strict_types=1);

namespace App\Tests\Unit\Donation\Infrastructure;

use App\Donation\Domain\Port\DonationGateway;
use App\Donation\Domain\Port\GatewayPayment;

/**
 * Deterministic in-memory DonationGateway used in unit tests.
 *
 * - Returns sequential `pi_donation_<n>` identifiers paired with `<id>_secret_<n>`
 *   client secrets so assertions can reason about exact values.
 * - Records every call so tests can verify which side-effects were triggered.
 */
final class InMemoryDonationGateway implements DonationGateway
{
    /** @var list<array{amountCents: int, currency: string, description: string, metadata: array<string, string|int|float|bool|null>}> */
    public array $calls = [];

    private int $counter = 0;

    public function createPayment(
        int $amountCents,
        string $currency,
        string $description,
        array $metadata,
    ): GatewayPayment {
        ++$this->counter;
        $paymentIntentId = \sprintf('pi_donation_%d', $this->counter);
        $clientSecret = \sprintf('%s_secret_%d', $paymentIntentId, $this->counter);

        $this->calls[] = [
            'amountCents' => $amountCents,
            'currency' => $currency,
            'description' => $description,
            'metadata' => $metadata,
        ];

        return new GatewayPayment(
            paymentIntentId: $paymentIntentId,
            clientSecret: $clientSecret,
        );
    }
}
