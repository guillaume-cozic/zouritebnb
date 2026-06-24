<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\UpdateUserProfile;
use App\User\Domain\Command\UpdateUserProfileCommand;

/**
 * @implements ProcessorInterface<UpdateUserProfileInput, void>
 */
final readonly class UpdateUserProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateUserProfile $useCase,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateUserProfileInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateUserProfileInput::class, get_debug_type($data)));
        }

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateUserProfileCommand(
            id: $this->currentUser->id(),
            firstName: $data->firstName ?: null,
            lastName: $data->lastName ?: null,
            email: $data->email,
            bio: $data->bio ?: null,
        )));
    }
}
