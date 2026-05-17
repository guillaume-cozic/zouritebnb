<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\CreateAccommodation;
use App\Accommodation\Domain\Command\CreateAccommodationCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<AccommodationInput, AccommodationOutput>
 */
final readonly class CreateAccommodationProcessor implements ProcessorInterface
{
    private const DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';
    private const DEFAULT_REGION_UUID = '00000000-0000-4000-8000-00000000000a';

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
            teamId: Uuid::fromString(self::DEFAULT_TEAM_UUID),
            regionId: Uuid::fromString(self::DEFAULT_REGION_UUID),
        )));

        $output = new AccommodationOutput();
        $output->id = $id;

        return $output;
    }
}
