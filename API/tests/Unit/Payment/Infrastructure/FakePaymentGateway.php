<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Infrastructure;

use App\Payment\Domain\Port\GatewayAuthorization;
use App\Payment\Domain\Port\PaymentGateway;

/**
 * Deterministic in-memory PaymentGateway used in unit tests.
 *
 * - Returns sequential `pi_test_<n>` identifiers paired with `<id>_secret_<n>` client
 *   secrets so assertions can reason about exact values.
 * - Records every call so tests can verify which side-effects were triggered.
 */
final class FakePaymentGateway implements PaymentGateway
{
    /** @var list<array{type: string, paymentIntentId?: string, amountCents?: int, currency?: string, description?: string, metadata?: array<string, string|int|float|bool|null>}> */
    public array $calls = [];

    private int $counter = 0;

    public function createAuthorization(
        int $amountCents,
        string $currency,
        string $description,
        array $metadata,
    ): GatewayAuthorization {
        ++$this->counter;
        $paymentIntentId = \sprintf('pi_test_%d', $this->counter);
        $clientSecret = \sprintf('%s_secret_%d', $paymentIntentId, $this->counter);

        $this->calls[] = [
            'type' => 'createAuthorization',
            'paymentIntentId' => $paymentIntentId,
            'amountCents' => $amountCents,
            'currency' => $currency,
            'description' => $description,
            'metadata' => $metadata,
        ];

        return new GatewayAuthorization(
            paymentIntentId: $paymentIntentId,
            clientSecret: $clientSecret,
        );
    }

    public function capture(string $paymentIntentId): void
    {
        $this->calls[] = ['type' => 'capture', 'paymentIntentId' => $paymentIntentId];
    }

    public function cancel(string $paymentIntentId): void
    {
        $this->calls[] = ['type' => 'cancel', 'paymentIntentId' => $paymentIntentId];
    }

    public function refund(string $paymentIntentId, int $amountCents): void
    {
        $this->calls[] = ['type' => 'refund', 'paymentIntentId' => $paymentIntentId, 'amountCents' => $amountCents];
    }
}
