<?php

declare(strict_types=1);

namespace App\Tests\E2e\Payment;

use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;

final class CreatePaymentIntentTest extends PaymentApiTestCase
{
    public function test_should_create_payment_intent(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'amountCents' => 25000,
                'currency' => 'eur',
                'description' => 'Réservation Maison du lagon — 10 au 15 juin 2026',
                'metadata' => [
                    'accommodationId' => 'abc',
                    'nights' => 5,
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'paymentIntentId' => 'pi_test_1',
            'clientSecret' => 'pi_test_1_secret_1',
        ]);

        self::assertCount(1, $gateway->calls);
        self::assertSame('createAuthorization', $gateway->calls[0]['type']);
        self::assertSame(25000, $gateway->calls[0]['amountCents']);
        self::assertSame('eur', $gateway->calls[0]['currency']);
        self::assertSame('Réservation Maison du lagon — 10 au 15 juin 2026', $gateway->calls[0]['description']);
        self::assertSame(['accommodationId' => 'abc', 'nights' => 5], $gateway->calls[0]['metadata']);
    }

    public function test_should_create_payment_intent_without_metadata(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'amountCents' => 12000,
                'currency' => 'EUR',
                'description' => 'Réservation sans métadonnées',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'paymentIntentId' => 'pi_test_1',
            'clientSecret' => 'pi_test_1_secret_1',
        ]);

        self::assertCount(1, $gateway->calls);
        self::assertSame([], $gateway->calls[0]['metadata']);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);

        $client->request('POST', '/api/payment-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'amountCents' => 25000,
                'currency' => 'eur',
                'description' => 'Réservation sans authentification',
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_when_amount_is_zero(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'amountCents' => 0,
                'currency' => 'eur',
                'description' => 'Montant invalide',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_amount_is_negative(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'amountCents' => -100,
                'currency' => 'eur',
                'description' => 'Montant négatif',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_with_violation_when_amount_is_missing(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'currency' => 'eur',
                'description' => 'Montant absent',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'amountCents']]]);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_with_violation_when_currency_is_missing(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'amountCents' => 25000,
                'description' => 'Devise absente',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'currency']]]);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_when_currency_is_invalid(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'amountCents' => 25000,
                'currency' => 'EUROS',
                'description' => 'Devise invalide',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
