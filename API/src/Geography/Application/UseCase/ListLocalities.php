<?php

declare(strict_types=1);

namespace App\Geography\Application\UseCase;

use App\Geography\Domain\Command\ListLocalitiesCommand;
use App\Geography\Domain\Entity\Locality;
use App\Geography\Domain\Port\LocalityRepository;

final readonly class ListLocalities
{
    public function __construct(
        private LocalityRepository $repository,
    ) {
    }

    /**
     * @return Locality[]
     */
    public function handle(ListLocalitiesCommand $command): array
    {
        if (null !== $command->regionCode) {
            return $this->repository->findByRegionCode($command->regionCode);
        }

        return $this->repository->findAll();
    }
}
