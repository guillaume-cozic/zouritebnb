<?php

declare(strict_types=1);

namespace App\Tests\Integration\Geography\Infrastructure;

use App\Geography\Domain\Entity\Region;
use App\Geography\Domain\Port\RegionRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineRegionRepositoryTest extends RepositoryTestCase
{
    private RegionRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(RegionRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $region = new Region(
            id: $id,
            code: 'IDF',
            name: 'Île-de-France',
        );

        $this->repository->save($region);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('IDF', $found->getCode());
        self::assertSame('Île-de-France', $found->getName());
    }

    public function test_should_return_null_when_not_found_by_id(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $region = new Region(
            id: $id,
            code: 'PACA',
            name: 'Old Name',
        );
        $this->repository->save($region);

        $updated = new Region(
            id: $id,
            code: 'PACA',
            name: 'Provence-Alpes-Côte d\'Azur',
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('PACA', $found->getCode());
        self::assertSame('Provence-Alpes-Côte d\'Azur', $found->getName());
    }

    public function test_should_find_by_code(): void
    {
        $id = Uuid::v4();
        $region = new Region(
            id: $id,
            code: 'BRE',
            name: 'Bretagne',
        );
        $this->repository->save($region);

        $found = $this->repository->findByCode('BRE');

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('BRE', $found->getCode());
        self::assertSame('Bretagne', $found->getName());
    }

    public function test_should_return_null_when_not_found_by_code(): void
    {
        $result = $this->repository->findByCode('UNKNOWN_CODE');

        self::assertNull($result);
    }

    public function test_should_find_all_ordered_by_name(): void
    {
        $this->repository->save(new Region(
            id: Uuid::v4(),
            code: 'NORMANDIE',
            name: 'Normandie',
        ));
        $this->repository->save(new Region(
            id: Uuid::v4(),
            code: 'CORSE',
            name: 'Corse',
        ));
        $this->repository->save(new Region(
            id: Uuid::v4(),
            code: 'OCCITANIE',
            name: 'Occitanie',
        ));

        $regions = $this->repository->findAll();

        $names = array_map(static fn (Region $region): string => $region->getName(), $regions);

        self::assertContains('Normandie', $names);
        self::assertContains('Corse', $names);
        self::assertContains('Occitanie', $names);

        $sorted = $names;
        sort($sorted, \SORT_STRING);
        self::assertSame($sorted, $names);
    }

    public function test_should_return_empty_array_when_no_region(): void
    {
        $regions = $this->repository->findAll();

        self::assertSame([], $regions);
    }
}
