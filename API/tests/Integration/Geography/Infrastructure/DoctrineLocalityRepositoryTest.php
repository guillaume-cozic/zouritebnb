<?php

declare(strict_types=1);

namespace App\Tests\Integration\Geography\Infrastructure;

use App\Geography\Domain\Entity\Locality;
use App\Geography\Domain\Entity\Region;
use App\Geography\Domain\Port\LocalityRepository;
use App\Geography\Domain\Port\RegionRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineLocalityRepositoryTest extends RepositoryTestCase
{
    private LocalityRepository $repository;
    private RegionRepository $regionRepository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(LocalityRepository::class);
        $this->regionRepository = self::getContainer()->get(RegionRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $regionId = Uuid::v4();
        $locality = new Locality(
            id: $id,
            name: 'Paris',
            regionId: $regionId,
        );

        $this->repository->save($locality);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('Paris', $found->getName());
        self::assertEquals($regionId, $found->getRegionId());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $regionId = Uuid::v4();
        $locality = new Locality(
            id: $id,
            name: 'Old Name',
            regionId: $regionId,
        );
        $this->repository->save($locality);

        $newRegionId = Uuid::v4();
        $updated = new Locality(
            id: $id,
            name: 'New Name',
            regionId: $newRegionId,
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('New Name', $found->getName());
        self::assertEquals($newRegionId, $found->getRegionId());

        $all = $this->repository->findAll();
        $matching = array_filter($all, static fn (Locality $l): bool => $l->getId()->equals($id));
        self::assertCount(1, $matching);
    }

    public function test_should_find_all_ordered_by_name(): void
    {
        $regionId = Uuid::v4();
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Zurich', regionId: $regionId));
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Amsterdam', regionId: $regionId));
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Madrid', regionId: $regionId));

        $all = $this->repository->findAll();

        $names = array_map(static fn (Locality $l): string => $l->getName(), $all);
        $sorted = $names;
        sort($sorted);
        self::assertSame($sorted, $names);

        self::assertContains('Zurich', $names);
        self::assertContains('Amsterdam', $names);
        self::assertContains('Madrid', $names);
    }

    public function test_should_return_empty_array_from_find_all_when_no_localities(): void
    {
        self::assertSame([], $this->repository->findAll());
    }

    public function test_should_find_by_region_id(): void
    {
        $regionId = Uuid::v4();
        $otherRegionId = Uuid::v4();

        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Lyon', regionId: $regionId));
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Bordeaux', regionId: $regionId));
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Berlin', regionId: $otherRegionId));

        $found = $this->repository->findByRegionId($regionId);

        self::assertCount(2, $found);
        $names = array_map(static fn (Locality $l): string => $l->getName(), $found);
        self::assertSame(['Bordeaux', 'Lyon'], $names);
    }

    public function test_should_return_empty_array_when_no_locality_for_region_id(): void
    {
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Nice', regionId: Uuid::v4()));

        $found = $this->repository->findByRegionId(Uuid::v4());

        self::assertSame([], $found);
    }

    public function test_should_find_by_region_code(): void
    {
        $regionId = Uuid::v4();
        $otherRegionId = Uuid::v4();

        $this->regionRepository->save(new Region(id: $regionId, code: 'IDF', name: 'Ile-de-France'));
        $this->regionRepository->save(new Region(id: $otherRegionId, code: 'PACA', name: 'Provence'));

        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Versailles', regionId: $regionId));
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Meaux', regionId: $regionId));
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Marseille', regionId: $otherRegionId));

        $found = $this->repository->findByRegionCode('IDF');

        self::assertCount(2, $found);
        $names = array_map(static fn (Locality $l): string => $l->getName(), $found);
        self::assertSame(['Meaux', 'Versailles'], $names);
    }

    public function test_should_return_empty_array_when_no_locality_for_region_code(): void
    {
        $regionId = Uuid::v4();
        $this->regionRepository->save(new Region(id: $regionId, code: 'NOR', name: 'Normandie'));
        $this->repository->save(new Locality(id: Uuid::v4(), name: 'Rouen', regionId: $regionId));

        $found = $this->repository->findByRegionCode('UNKNOWN');

        self::assertSame([], $found);
    }
}
