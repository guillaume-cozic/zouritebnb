<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\Stripe;

use App\Payment\Domain\Port\GatewayAuthorization;
use App\Payment\Domain\Port\PaymentGateway;
use Stripe\StripeClient;

final readonly class StripePaymentGateway implements PaymentGateway
{
    public function __construct(private StripeClient $stripeClient)
    {
    }

    public function createAuthorization(
        int $amountCents,
        string $currency,
        string $description,
        array $metadata,
    ): GatewayAuthorization {
        $intent = $this->stripeClient->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => strtolower($currency),
            'capture_method' => 'manual',
            'description' => $description,
            'metadata' => $this->normalizeMetadata($metadata),
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return new GatewayAuthorization(
            paymentIntentId: $intent->id,
            clientSecret: (string) $intent->client_secret,
        );
    }

    public function capture(string $paymentIntentId): void
    {
        $this->stripeClient->paymentIntents->capture($paymentIntentId);
    }

    public function cancel(string $paymentIntentId): void
    {
        $this->stripeClient->paymentIntents->cancel($paymentIntentId);
    }

    public function refund(string $paymentIntentId, int $amountCents): void
    {
        $this->stripeClient->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amountCents,
        ]);
    }

    /**
     * Stripe metadata must be string-keyed and scalar-valued.
     *
     * @param array<string, string|int|float|bool|null> $metadata
     *
     * @return array<string, string>
     */
    private function normalizeMetadata(array $metadata): array
    {
        $normalized = [];
        foreach ($metadata as $key => $value) {
            if (null === $value) {
                continue;
            }
            $normalized[$key] = \is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }

        return $normalized;
    }
}
