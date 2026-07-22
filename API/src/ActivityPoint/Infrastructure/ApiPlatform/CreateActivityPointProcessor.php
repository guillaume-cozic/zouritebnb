<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ActivityPoint\Application\UseCase\CreateActivityPoint;
use App\ActivityPoint\Domain\Command\CreateActivityPointCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;

/**
 * @implements ProcessorInterface<AdminActivityPointInput, void>
 */
final readonly class CreateActivityPointProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateActivityPoint $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof AdminActivityPointInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', AdminActivityPointInput::class, get_debug_type($data)));
        }

        $this->handler->execute(fn () => $this->useCase->handle(new CreateActivityPointCommand(
            name: $data->name,
            description: $data->description,
            category: $data->category,
            latitude: $data->latitude,
            longitude: $data->longitude,
            articleUrl: $data->articleUrl,
        )));
    }
}
