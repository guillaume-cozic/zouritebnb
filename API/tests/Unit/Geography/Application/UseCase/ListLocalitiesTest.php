<?php

declare(strict_types=1);

namespace App\Tests\Unit\Geography\Application\UseCase;

use App\Geography\Application\UseCase\ListLocalities;
use App\Geography\Domain\Command\ListLocalitiesCommand;
use App\Geography\Domain\Entity\Locality;
use App\Tests\Unit\Geography\Infrastructure\InMemoryLocalityRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListLocalitiesTest extends TestCase
{
    private InMemoryLocalityRepository $repository;
    private ListLocalities $useCase;

    private Uuid $regionA;
    private Uuid $regionB;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryLocalityRepository();
        $this->useCase = new ListLocalities($this->repository);

        $this->regionA = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $this->regionB = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a2');

        $this->repository->registerRegionCode($this->regionA, 'IDF');
        $this->repository->registerRegionCode($this->regionB, 'PACA');

        $this->saveLocality('01961e2f-dead-7000-beef-000000000001', 'Paris', $this->regionA);
        $this->saveLocality('01961e2f-dead-7000-beef-000000000002', 'Versailles', $this->regionA);
        $this->saveLocality('01961e2f-dead-7000-beef-000000000003', 'Nice', $this->regionB);
    }

    public function test_should_return_all_localities_when_no_region_code_given(): void
    {
        $results = $this->useCase->handle(new ListLocalitiesCommand());

        self::assertCount(3, $results);
        self::assertContainsOnlyInstancesOf(Locality::class, $results);
    }

    public function test_should_return_all_localities_when_region_code_is_null(): void
    {
        $results = $this->useCase->handle(new ListLocalitiesCommand(regionCode: null));

        self::assertCount(3, $results);
    }

    public function test_should_filter_localities_by_region_code(): void
    {
        $results = $this->useCase->handle(new ListLocalitiesCommand(regionCode: 'IDF'));

        self::assertCount(2, $results);
        $names = array_map(static fn (Locality $l): string => $l->getName(), $results);
        sort($names);
        self::assertSame(['Paris', 'Versailles'], $names);
    }

    public function test_should_return_empty_array_for_unknown_region_code(): void
    {
        $results = $this->useCase->handle(new ListLocalitiesCommand(regionCode: 'UNKNOWN'));

        self::assertSame([], $results);
    }

    private function saveLocality(string $idStr, string $name, Uuid $regionId): void
    {
        $this->repository->save(new Locality(
            id: Uuid::fromString($idStr),
            name: $name,
            regionId: $regionId,
        ));
    }
}
