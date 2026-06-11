<?php

declare(strict_types=1);

namespace App\Tests\E2e\Payment;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class CreatePaymentIntentTest extends PaymentApiTestCase
{
    private const string TEAM_ID = '00000000-0000-4000-8000-000000000abc';

    public function test_should_create_payment_intent_with_server_derived_amount(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');
        // 100€/night accommodation; a 4-night stay must be charged 40000 cents.
        $accommodationId = $this->insertAccommodation(price: 100.0);

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-06-10T15:00:00',
                'checkOut' => '2026-06-14T11:00:00',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'paymentIntentId' => 'pi_test_1',
            'clientSecret' => 'pi_test_1_secret_1',
        ]);

        self::assertCount(1, $gateway->calls);
        self::assertSame(40000, $gateway->calls[0]['amountCents']);
        self::assertSame('eur', $gateway->calls[0]['currency']);
    }

    public function test_should_ignore_any_client_supplied_amount(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');
        $accommodationId = $this->insertAccommodation(price: 100.0);

        // An attacker tries to pay 1 cent by injecting amountCents/currency.
        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-06-10T15:00:00',
                'checkOut' => '2026-06-14T11:00:00',
                'amountCents' => 1,
                'currency' => 'usd',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        // The injected fields are ignored: the amount stays server-derived.
        self::assertSame(40000, $gateway->calls[0]['amountCents']);
        self::assertSame('eur', $gateway->calls[0]['currency']);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $accommodationId = $this->insertAccommodation(price: 100.0);

        $client->request('POST', '/api/payment-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-06-10T15:00:00',
                'checkOut' => '2026-06-14T11:00:00',
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_with_violation_when_accommodation_id_is_missing(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'checkIn' => '2026-06-10T15:00:00',
                'checkOut' => '2026-06-14T11:00:00',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'accommodationId']]]);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_when_accommodation_does_not_exist(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'traveller@example.com');

        $client->request('POST', '/api/payment-intents', [
            'headers' => $this->authHeaders('traveller@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => Uuid::v7()->toRfc4122(),
                'checkIn' => '2026-06-10T15:00:00',
                'checkOut' => '2026-06-14T11:00:00',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertCount(0, $gateway->calls);
    }

    private function insertAccommodation(float $price): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle('Maison du lagon')
            ->setDescription('Vue mer')
            ->setPrice($price)
            ->setStatus('published')
            ->setTeamId(Uuid::fromString(self::TEAM_ID));

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
