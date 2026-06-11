<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\CreateAccommodation;
use App\Accommodation\Domain\Command\CreateAccommodationCommand;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<AccommodationInput, AccommodationOutput>
 */
final readonly class CreateAccommodationProcessor implements ProcessorInterface
{
    private const DEFAULT_REGION_UUID = '00000000-0000-4000-8000-00000000000a';

    public function __construct(
        private CreateAccommodation $createAccommodation,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AccommodationOutput
    {
        if (!$data instanceof AccommodationInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', AccommodationInput::class, get_debug_type($data)));
        }

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->createAccommodation->handle(new CreateAccommodationCommand(
            title: $data->title,
            description: $data->description,
            price: $data->price,
            teamId: $this->currentUser->teamId(),
            regionId: Uuid::fromString(self::DEFAULT_REGION_UUID),
        )));

        $output = new AccommodationOutput();
        $output->id = $id;

        return $output;
    }
}
