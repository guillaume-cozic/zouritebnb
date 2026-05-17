<?php

declare(strict_types=1);

namespace App\Geography\Application\UseCase;

use App\Geography\Domain\Entity\Region;
use App\Geography\Domain\Port\RegionRepository;

final readonly class ListRegions
{
    public function __construct(
        private RegionRepository $repository,
    ) {
    }

    /**
     * @return Region[]
     */
    public function handle(): array
    {
        return $this->repository->findAll();
    }
}
