<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\RequestReservationModification;
use App\Reservation\Domain\Command\RequestReservationModificationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Reservation\Infrastructure\Security\ReservationAccessGuard;
use App\Shared\Domain\Port\Clock;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<RequestReservationModificationInput, ReservationOutput>
 */
final readonly class RequestReservationModificationProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestReservationModification $requestModification,
        private ReservationRepository $repository,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
        private ReservationAccessGuard $accessGuard,
        private Clock $clock,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReservationOutput
    {
        if (!$data instanceof RequestReservationModificationInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', RequestReservationModificationInput::class, get_debug_type($data)));
        }

        $id = (string) $uriVariables['id'];

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        if (null === $reservation) {
            throw ReservationNotFoundException::becauseId($id);
        }

        $this->accessGuard->assertGuest($reservation, $this->currentUser);

        $this->handler->execute(fn () => $this->requestModification->handle(new RequestReservationModificationCommand(
            reservationId: $id,
            checkIn: new \DateTimeImmutable((string) $data->checkIn),
            checkOut: new \DateTimeImmutable((string) $data->checkOut),
        )));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        if (null === $reservation) {
            throw new \RuntimeException('Reservation could not be reloaded after the operation.');
        }

        return ReservationOutput::fromEntity($reservation, $this->clock->now());
    }
}
