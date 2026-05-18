<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\Stripe;

use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

final readonly class StripeWebhookSignatureVerifier
{
    public function __construct(private string $webhookSecret)
    {
    }

    /**
     * @throws SignatureVerificationException when the signature is invalid
     * @throws UnexpectedValueException       when the payload is malformed
     */
    public function verify(string $payload, string $signatureHeader): Event
    {
        return Webhook::constructEvent($payload, $signatureHeader, $this->webhookSecret);
    }
}
