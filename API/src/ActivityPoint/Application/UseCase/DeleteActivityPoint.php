<?php

declare(strict_types=1);

namespace App\ActivityPoint\Application\UseCase;

use App\ActivityPoint\Domain\Command\DeleteActivityPointCommand;
use App\ActivityPoint\Domain\Exception\ActivityPointNotFoundException;
use App\ActivityPoint\Domain\Port\ActivityPointRepository;

final readonly class DeleteActivityPoint
{
    public function __construct(private ActivityPointRepository $repository)
    {
    }

    public function handle(DeleteActivityPointCommand $command): void
    {
        $point = $this->repository->findById($command->id);

        if (null === $point) {
            throw ActivityPointNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        $this->repository->remove($command->id);
    }
}
