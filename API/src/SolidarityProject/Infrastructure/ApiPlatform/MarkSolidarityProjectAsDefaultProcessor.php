<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\SolidarityProject\Application\UseCase\MarkSolidarityProjectAsDefault;
use App\SolidarityProject\Domain\Command\MarkSolidarityProjectAsDefaultCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class MarkSolidarityProjectAsDefaultProcessor implements ProcessorInterface
{
    public function __construct(
        private MarkSolidarityProjectAsDefault $useCase,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        // Platform curation action reserved for ROLE_ADMIN (enforced by the
        // operation's security expression). Resolving the current user adds a
        // defense-in-depth 401 if the endpoint is ever reached anonymously.
        $this->currentUser->id();

        $this->handler->execute(fn () => $this->useCase->handle(new MarkSolidarityProjectAsDefaultCommand(
            projectId: Uuid::fromString($uriVariables['id']),
        )));
    }
}
