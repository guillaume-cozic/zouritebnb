<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\CreateAccommodation;
use App\Accommodation\Domain\Command\CreateAccommodationCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;

/**
 * @implements ProcessorInterface<AccommodationInput, AccommodationOutput>
 */
final readonly class CreateAccommodationProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateAccommodation $createAccommodation,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AccommodationOutput
    {
        \assert($data instanceof AccommodationInput);

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->createAccommodation->handle(new CreateAccommodationCommand(
            title: $data->title,
            description: $data->description,
            price: $data->price,
        )));

        $output = new AccommodationOutput();
        $output->id = $id;

        return $output;
    }
}
