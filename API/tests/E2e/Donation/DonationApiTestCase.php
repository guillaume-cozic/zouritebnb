<?php

declare(strict_types=1);

namespace App\Tests\E2e\Donation;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Donation\Domain\Port\DonationGateway;
use App\Donation\Infrastructure\Doctrine\DonationEntity;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use App\Tests\Unit\Donation\Infrastructure\InMemoryDonationGateway;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class DonationApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    /**
     * Boots the test client with the real Stripe gateway swapped for a deterministic
     * {@see InMemoryDonationGateway}, so the use case never performs a real HTTP call
     * to Stripe.
     *
     * The fake is passed in so individual tests can inspect the recorded gateway calls.
     */
    protected function createClientWithFakeGateway(InMemoryDonationGateway $gateway): Client
    {
        $client = self::createClient();

        self::getContainer()->set(DonationGateway::class, $gateway);

        return $client;
    }

    /**
     * Persists a SolidarityProject row and returns its UUID (RFC4122).
     */
    protected function insertSolidarityProject(string $status = 'active'): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new SolidarityProjectEntity()
            ->setId(Uuid::v7())
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->setTranslations([
                'fr' => ['title' => 'Récifs coralliens', 'description' => 'Restauration des récifs.', 'keyFigures' => []],
            ]);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    /**
     * Reads back the persisted donation for a Stripe payment intent id, fresh from the
     * database (the identity map is cleared first so we observe the request's side effects).
     */
    protected function findDonation(string $paymentIntentId): ?DonationEntity
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        return $em->getRepository(DonationEntity::class)
            ->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
    }
}
