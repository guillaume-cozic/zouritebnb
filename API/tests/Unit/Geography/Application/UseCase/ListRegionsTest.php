<?php

declare(strict_types=1);

namespace App\Tests\Unit\Geography\Application\UseCase;

use App\Geography\Application\UseCase\ListRegions;
use App\Geography\Domain\Entity\Region;
use App\Tests\Unit\Geography\Infrastructure\InMemoryRegionRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListRegionsTest extends TestCase
{
    private InMemoryRegionRepository $repository;
    private ListRegions $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryRegionRepository();
        $this->useCase = new ListRegions($this->repository);
    }

    public function test_should_return_empty_array_when_no_region_exists(): void
    {
        $results = $this->useCase->handle();

        self::assertSame([], $results);
    }

    public function test_should_return_all_regions(): void
    {
        $this->saveRegion('01961e2f-dead-7000-beef-0000000000a1', 'IDF', 'Ile-de-France');
        $this->saveRegion('01961e2f-dead-7000-beef-0000000000a2', 'PACA', 'Provence-Alpes-Cote d Azur');

        $results = $this->useCase->handle();

        self::assertCount(2, $results);
        self::assertContainsOnlyInstancesOf(Region::class, $results);

        $codes = array_map(static fn (Region $r): string => $r->getCode(), $results);
        sort($codes);
        self::assertSame(['IDF', 'PACA'], $codes);
    }

    private function saveRegion(string $idStr, string $code, string $name): void
    {
        $this->repository->save(new Region(
            id: Uuid::fromString($idStr),
            code: $code,
            name: $name,
        ));
    }
}
