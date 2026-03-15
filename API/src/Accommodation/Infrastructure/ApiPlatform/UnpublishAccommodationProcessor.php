<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UnpublishAccommodation;
use App\Accommodation\Domain\Command\UnpublishAccommodationCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<AccommodationOutput, void>
 */
final readonly class UnpublishAccommodationProcessor implements ProcessorInterface
{
    public function __construct(
        private UnpublishAccommodation $unpublishAccommodation,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->unpublishAccommodation->handle(new UnpublishAccommodationCommand(
            id: Uuid::fromString($uriVariables['id']),
        )));
    }
}
