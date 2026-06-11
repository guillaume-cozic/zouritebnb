<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Application\UseCase\UpdateTeamFavoriteSolidarityProject;
use App\Team\Domain\Command\UpdateTeamFavoriteSolidarityProjectCommand;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateTeamFavoriteSolidarityProjectInput, void>
 */
final readonly class UpdateTeamFavoriteSolidarityProjectProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateTeamFavoriteSolidarityProject $useCase,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateTeamFavoriteSolidarityProjectInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateTeamFavoriteSolidarityProjectInput::class, get_debug_type($data)));
        }

        $teamId = $this->currentUser->teamId();

        if (!$teamId->equals(Uuid::fromString($uriVariables['id']))) {
            throw new AccessDeniedHttpException('You can only manage the favorite solidarity project of your own team.');
        }

        $projectId = null !== $data->favoriteSolidarityProjectId && '' !== $data->favoriteSolidarityProjectId
            ? Uuid::fromString($data->favoriteSolidarityProjectId)
            : null;

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateTeamFavoriteSolidarityProjectCommand(
            teamId: $teamId,
            favoriteSolidarityProjectId: $projectId,
        )));
    }
}
