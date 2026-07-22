<?php

declare(strict_types=1);

namespace App\Tests\E2e\Donation;

use App\Tests\Unit\Donation\Infrastructure\InMemoryDonationGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

final class CreateDonationIntentTest extends DonationApiTestCase
{
    public function test_should_create_donation_intent_without_authentication(): void
    {
        $gateway = new InMemoryDonationGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $projectId = $this->insertSolidarityProject();

        // No Authorization header: donations are anonymous, the endpoint is public.
        $client->request('POST', '/api/donation-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'solidarityProjectId' => $projectId,
                'amountCents' => 500,
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'paymentIntentId' => 'pi_donation_1',
            'clientSecret' => 'pi_donation_1_secret_1',
        ]);

        self::assertCount(1, $gateway->calls);
        self::assertSame(500, $gateway->calls[0]['amountCents']);
        self::assertSame('eur', $gateway->calls[0]['currency']);

        $donation = $this->findDonation('pi_donation_1');
        self::assertNotNull($donation);
        self::assertSame('pending', $donation->getStatus());
        self::assertSame(500, $donation->getAmountCents());
        self::assertSame('eur', $donation->getCurrency());
        self::assertSame($projectId, $donation->getSolidarityProjectId()?->toRfc4122());
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function boundaryAmountsProvider(): \Generator
    {
        yield 'minimum amount 100 cents (1 euro)' => [100];
        yield 'maximum amount 1000000 cents (10000 euros)' => [1000000];
    }

    #[DataProvider('boundaryAmountsProvider')]
    public function test_should_accept_boundary_amount(int $amountCents): void
    {
        $gateway = new InMemoryDonationGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $projectId = $this->insertSolidarityProject();

        $client->request('POST', '/api/donation-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'solidarityProjectId' => $projectId,
                'amountCents' => $amountCents,
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame($amountCents, $gateway->calls[0]['amountCents']);
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function outOfBoundsAmountsProvider(): \Generator
    {
        yield 'below minimum (99 cents)' => [99];
        yield 'above maximum (1000001 cents)' => [1000001];
    }

    #[DataProvider('outOfBoundsAmountsProvider')]
    public function test_should_return422_when_amount_out_of_bounds(int $amountCents): void
    {
        $gateway = new InMemoryDonationGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $projectId = $this->insertSolidarityProject();

        $client->request('POST', '/api/donation-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'solidarityProjectId' => $projectId,
                'amountCents' => $amountCents,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_when_project_does_not_exist(): void
    {
        $gateway = new InMemoryDonationGateway();
        $client = $this->createClientWithFakeGateway($gateway);

        $client->request('POST', '/api/donation-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'solidarityProjectId' => Uuid::v7()->toRfc4122(),
                'amountCents' => 500,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_when_project_is_closed(): void
    {
        $gateway = new InMemoryDonationGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $projectId = $this->insertSolidarityProject(status: 'closed');

        $client->request('POST', '/api/donation-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'solidarityProjectId' => $projectId,
                'amountCents' => 500,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_with_violation_when_project_id_is_not_a_uuid(): void
    {
        $gateway = new InMemoryDonationGateway();
        $client = $this->createClientWithFakeGateway($gateway);

        $client->request('POST', '/api/donation-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'solidarityProjectId' => 'not-a-uuid',
                'amountCents' => 500,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'solidarityProjectId']]]);
        self::assertCount(0, $gateway->calls);
    }

    public function test_should_return422_with_violation_when_amount_is_missing(): void
    {
        $gateway = new InMemoryDonationGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $projectId = $this->insertSolidarityProject();

        $client->request('POST', '/api/donation-intents', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'solidarityProjectId' => $projectId,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'amountCents']]]);
        self::assertCount(0, $gateway->calls);
    }
}
