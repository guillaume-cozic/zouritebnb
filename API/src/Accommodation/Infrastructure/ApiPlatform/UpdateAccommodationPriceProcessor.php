<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationPrice;
use App\Accommodation\Domain\Command\UpdateAccommodationPriceCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationPriceInput, void>
 */
final readonly class UpdateAccommodationPriceProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationPrice $updateAccommodationPrice,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->updateAccommodationPrice->handle(new UpdateAccommodationPriceCommand(
            id: Uuid::fromString($uriVariables['id']),
            price: $data->price,
        )));
    }
}
