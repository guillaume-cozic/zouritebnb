<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\UpdateUserProfile;
use App\User\Domain\Command\UpdateUserProfileCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateUserProfileInput, void>
 */
final readonly class UpdateUserProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateUserProfile $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof UpdateUserProfileInput);

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateUserProfileCommand(
            id: Uuid::fromString($uriVariables['id']),
            firstName: $data->firstName ?: null,
            lastName: $data->lastName ?: null,
            email: $data->email,
        )));
    }
}
