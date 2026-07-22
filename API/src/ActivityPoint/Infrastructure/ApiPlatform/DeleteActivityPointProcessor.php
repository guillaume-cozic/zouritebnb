<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ActivityPoint\Application\UseCase\DeleteActivityPoint;
use App\ActivityPoint\Domain\Command\DeleteActivityPointCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class DeleteActivityPointProcessor implements ProcessorInterface
{
    public function __construct(
        private DeleteActivityPoint $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->useCase->handle(new DeleteActivityPointCommand(
            id: Uuid::fromString((string) $uriVariables['id']),
        )));
    }
}
