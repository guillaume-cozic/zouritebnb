<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\UpdateTeamFavoriteSolidarityProject;
use App\Team\Domain\Command\UpdateTeamFavoriteSolidarityProjectCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateTeamFavoriteSolidarityProjectInput, void>
 */
final readonly class UpdateTeamFavoriteSolidarityProjectProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateTeamFavoriteSolidarityProject $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $projectId = null !== $data->favoriteSolidarityProjectId && '' !== $data->favoriteSolidarityProjectId
            ? Uuid::fromString($data->favoriteSolidarityProjectId)
            : null;

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateTeamFavoriteSolidarityProjectCommand(
            teamId: Uuid::fromString($uriVariables['id']),
            favoriteSolidarityProjectId: $projectId,
        )));
    }
}
