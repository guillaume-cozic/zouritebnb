<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\SolidarityProject\Application\UseCase\UpdateSolidarityProject;
use App\SolidarityProject\Domain\Command\UpdateSolidarityProjectCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<AdminSolidarityProjectInput, void>
 */
final readonly class UpdateSolidarityProjectProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateSolidarityProject $useCase,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof AdminSolidarityProjectInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', AdminSolidarityProjectInput::class, get_debug_type($data)));
        }

        // Platform curation action reserved for ROLE_ADMIN (enforced by the
        // operation's security expression). Resolving the current user adds a
        // defense-in-depth 401 if the endpoint is ever reached anonymously.
        $this->currentUser->id();

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateSolidarityProjectCommand(
            projectId: Uuid::fromString($uriVariables['id']),
            translations: $data->toTranslations(),
            imageUrl: $data->imageUrl,
            status: $data->status,
        )));
    }
}
