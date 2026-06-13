<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\SolidarityProject\Application\UseCase\CreateSolidarityProject;
use App\SolidarityProject\Domain\Command\CreateSolidarityProjectCommand;

/**
 * @implements ProcessorInterface<AdminSolidarityProjectInput, AdminSolidarityProjectOutput>
 */
final readonly class CreateSolidarityProjectProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateSolidarityProject $useCase,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminSolidarityProjectOutput
    {
        if (!$data instanceof AdminSolidarityProjectInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', AdminSolidarityProjectInput::class, get_debug_type($data)));
        }

        // Platform curation action reserved for ROLE_ADMIN (enforced by the
        // operation's security expression). Resolving the current user adds a
        // defense-in-depth 401 if the endpoint is ever reached anonymously.
        $this->currentUser->id();

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->useCase->handle(new CreateSolidarityProjectCommand(
            title: $data->title,
            description: $data->description,
            imageUrl: $data->imageUrl,
            status: $data->status,
            keyFigures: $data->keyFigures,
        )));

        $output = new AdminSolidarityProjectOutput();
        $output->id = $id;
        $output->title = $data->title;
        $output->description = $data->description;
        $output->imageUrl = $data->imageUrl;
        $output->status = $data->status;
        $output->isDefault = false;
        $output->keyFigures = $data->keyFigures;

        return $output;
    }
}
