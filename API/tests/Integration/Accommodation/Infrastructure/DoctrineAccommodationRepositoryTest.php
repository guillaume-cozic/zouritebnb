<?php

declare(strict_types=1);

namespace App\Tests\Integration\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\AccommodationStatus;
use App\Accommodation\Domain\Entity\Address;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineAccommodationRepositoryTest extends RepositoryTestCase
{
    private AccommodationRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(AccommodationRepository::class);
    }

    public function testShouldSaveAndFindById(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Cozy Apartment',
            description: 'A nice place to stay',
            price: 120.50,
        );

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('Cozy Apartment', $found->getTitle());
        self::assertSame('A nice place to stay', $found->getDescription());
        self::assertSame(120.50, $found->getPrice());
        self::assertSame(AccommodationStatus::Draft, $found->getStatus());
    }

    public function testShouldReturnNullWhenNotFound(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function testShouldUpdateExistingEntity(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Old Title',
            description: 'Old description',
            price: 100.0,
        );
        $this->repository->save($accommodation);

        $updated = new Accommodation(
            id: $id,
            title: 'New Title',
            description: 'New description',
            price: 200.0,
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('New Title', $found->getTitle());
        self::assertSame('New description', $found->getDescription());
        self::assertSame(200.0, $found->getPrice());
    }

    public function testShouldPersistPublishedStatus(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Beach House',
            description: 'By the sea',
            price: 350.0,
            status: AccommodationStatus::Published,
        );

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame(AccommodationStatus::Published, $found->getStatus());
    }

    public function testShouldSaveAndFindAccommodationWithAddress(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Mountain Lodge',
            description: 'In the Alps',
            price: 250.0,
        );

        $address = new Address(
            street: '10 Rue de la Paix',
            city: 'Paris',
            zipCode: '75002',
            country: 'France',
        );
        $accommodation->updateAddress($address);
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getAddress());
        self::assertSame('10 Rue de la Paix', $found->getAddress()->street());
        self::assertSame('Paris', $found->getAddress()->city());
        self::assertSame('75002', $found->getAddress()->zipCode());
        self::assertSame('France', $found->getAddress()->country());
    }
}
