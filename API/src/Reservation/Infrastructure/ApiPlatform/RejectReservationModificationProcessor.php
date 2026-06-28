<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\RejectReservationModification;
use App\Reservation\Domain\Command\RejectReservationModificationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Reservation\Infrastructure\Security\ReservationAccessGuard;
use App\Shared\Domain\Port\Clock;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, ReservationOutput>
 */
final readonly class RejectReservationModificationProcessor implements ProcessorInterface
{
    public function __construct(
        private RejectReservationModification $rejectModification,
        private ReservationRepository $repository,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
        private ReservationAccessGuard $accessGuard,
        private Clock $clock,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReservationOutput
    {
        $id = (string) $uriVariables['id'];

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        if (null === $reservation) {
            throw ReservationNotFoundException::becauseId($id);
        }

        $this->accessGuard->assertHost($reservation, $this->currentUser);

        $this->handler->execute(fn () => $this->rejectModification->handle(new RejectReservationModificationCommand(reservationId: $id)));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        if (null === $reservation) {
            throw new \RuntimeException('Reservation could not be reloaded after the operation.');
        }

        return ReservationOutput::fromEntity($reservation, $this->clock->now());
    }
}
