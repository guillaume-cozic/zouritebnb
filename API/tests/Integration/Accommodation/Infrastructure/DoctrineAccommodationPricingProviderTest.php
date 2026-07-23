<?php

declare(strict_types=1);

namespace App\Tests\Integration\Accommodation\Infrastructure;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Shared\Domain\Port\AccommodationPricingProvider;
use App\Tests\Integration\RepositoryTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineAccommodationPricingProviderTest extends RepositoryTestCase
{
    private AccommodationPricingProvider $provider;
    private EntityManagerInterface $entityManager;

    #[Before]
    public function initProvider(): void
    {
        $this->provider = self::getContainer()->get(AccommodationPricingProvider::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function test_should_expose_only_extra_services_billed_with_the_reservation(): void
    {
        $id = Uuid::v4();
        $this->persistAccommodation($id, [
            ['name' => 'Ménage', 'price' => 30.0, 'billedWithReservation' => true],
            ['name' => 'Panier de bienvenue', 'price' => 20.0, 'billedWithReservation' => false],
            // A missing key means the service is paid on site, not with the reservation.
            ['name' => 'Bois de cheminée', 'price' => 10.0],
        ]);

        $pricing = $this->provider->findByAccommodationId($id);

        self::assertNotNull($pricing);
        self::assertSame([['name' => 'Ménage', 'price' => 30.0]], $pricing->billedExtraServices);
    }

    public function test_should_expose_no_billed_extra_services_when_accommodation_has_none(): void
    {
        $id = Uuid::v4();
        $this->persistAccommodation($id, null);

        $pricing = $this->provider->findByAccommodationId($id);

        self::assertNotNull($pricing);
        self::assertSame([], $pricing->billedExtraServices);
    }

    public function test_should_return_null_when_accommodation_not_found(): void
    {
        self::assertNull($this->provider->findByAccommodationId(Uuid::v4()));
    }

    /** @param array<array<string, mixed>>|null $extraServices */
    private function persistAccommodation(Uuid $id, ?array $extraServices): void
    {
        $entity = new AccommodationEntity();
        $entity
            ->setId($id)
            ->setTitle('Gîte des tests')
            ->setDescription('Un hébergement de test.')
            ->setPrice(100.0)
            ->setExtraServices($extraServices);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
