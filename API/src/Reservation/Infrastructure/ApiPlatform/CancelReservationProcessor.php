<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\CancelReservation;
use App\Reservation\Domain\Command\CancelReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, ReservationOutput>
 */
final readonly class CancelReservationProcessor implements ProcessorInterface
{
    public function __construct(
        private CancelReservation $cancelReservation,
        private ReservationRepository $repository,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReservationOutput
    {
        $id = (string) $uriVariables['id'];

        $this->handler->execute(fn () => $this->cancelReservation->handle(new CancelReservationCommand(
            reservationId: $id,
        )));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        \assert(null !== $reservation);

        return ReservationOutput::fromEntity($reservation);
    }
}
