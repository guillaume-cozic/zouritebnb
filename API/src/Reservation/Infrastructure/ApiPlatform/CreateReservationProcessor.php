<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\CreateReservation;
use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<ReservationInput, ReservationOutput>
 */
final readonly class CreateReservationProcessor implements ProcessorInterface
{
    private const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    public function __construct(
        private CreateReservation $createReservation,
        private ReservationRepository $repository,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReservationOutput
    {
        \assert($data instanceof ReservationInput);

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->createReservation->handle(new CreateReservationCommand(
            accommodationId: Uuid::fromString($data->accommodationId),
            teamId: Uuid::fromString(self::DEFAULT_TEAM_UUID),
            checkIn: new \DateTimeImmutable($data->checkIn),
            checkOut: new \DateTimeImmutable($data->checkOut),
            guestName: $data->guestName,
        )));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        \assert(null !== $reservation);

        return ReservationOutput::fromEntity($reservation);
    }
}
