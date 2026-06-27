<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\CancelReservation;
use App\Reservation\Domain\Command\CancelReservationCommand;
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
final readonly class CancelReservationProcessor implements ProcessorInterface
{
    public function __construct(
        private CancelReservation $cancelReservation,
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

        $this->accessGuard->assertHostOrGuest($reservation, $this->currentUser);

        // A cancellation by the host fully refunds the guest (full compensation),
        // whereas a guest cancellation follows the snapshotted policy.
        $byHost = $this->accessGuard->isHost($reservation, $this->currentUser);

        $message = $data instanceof CancelReservationInput ? $data->message : null;

        $this->handler->execute(fn () => $this->cancelReservation->handle(new CancelReservationCommand(
            reservationId: $id,
            message: $message,
            byHost: $byHost,
        )));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        if (null === $reservation) {
            throw new \RuntimeException('Reservation could not be reloaded after the operation.');
        }

        return ReservationOutput::fromEntity($reservation, $this->clock->now(), $byHost);
    }
}
