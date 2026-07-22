<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ActivityPoint\Application\UseCase\UpdateActivityPoint;
use App\ActivityPoint\Domain\Command\UpdateActivityPointCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<AdminActivityPointInput, void>
 */
final readonly class UpdateActivityPointProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateActivityPoint $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof AdminActivityPointInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', AdminActivityPointInput::class, get_debug_type($data)));
        }

        $this->handler->execute(fn () => $this->useCase->handle(new UpdateActivityPointCommand(
            id: Uuid::fromString((string) $uriVariables['id']),
            name: $data->name,
            description: $data->description,
            category: $data->category,
            latitude: $data->latitude,
            longitude: $data->longitude,
            articleUrl: $data->articleUrl,
        )));
    }
}
