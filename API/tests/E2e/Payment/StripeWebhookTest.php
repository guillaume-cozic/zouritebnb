<?php

declare(strict_types=1);

namespace App\Tests\E2e\Payment;

/**
 * The Stripe webhook must stay publicly reachable (Stripe's servers cannot present a JWT)
 * while being protected by HMAC signature verification rather than by authentication.
 *
 * These tests assert the route is NOT behind the JWT firewall: an unauthenticated request
 * is rejected for a bad signature (400), never for missing authentication (401).
 */
final class StripeWebhookTest extends PaymentApiTestCase
{
    public function test_should_not_require_authentication(): void
    {
        self::createClient()->request('POST', '/api/stripe/webhook', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Stripe-Signature' => 'invalid-signature',
            ],
            'body' => '{"id":"evt_test","type":"payment_intent.succeeded"}',
        ]);

        // Public route: rejected for the bad signature, not for a missing JWT.
        self::assertResponseStatusCodeSame(400);
    }

    public function test_should_reject_invalid_signature_without_a_token(): void
    {
        self::createClient()->request('POST', '/api/stripe/webhook', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Stripe-Signature' => '',
            ],
            'body' => '{"id":"evt_test","type":"payment_intent.payment_failed"}',
        ]);

        self::assertResponseStatusCodeSame(400);
    }
}
