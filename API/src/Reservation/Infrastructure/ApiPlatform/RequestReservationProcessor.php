<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\RequestReservation;
use App\Reservation\Domain\Command\RequestReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<RequestReservationInput, ReservationOutput>
 */
final readonly class RequestReservationProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestReservation $requestReservation,
        private ReservationRepository $repository,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReservationOutput
    {
        \assert($data instanceof RequestReservationInput);

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->requestReservation->handle(new RequestReservationCommand(
            accommodationId: Uuid::fromString($data->accommodationId),
            guestUserId: Uuid::fromString($data->guestUserId),
            checkIn: new \DateTimeImmutable($data->checkIn),
            checkOut: new \DateTimeImmutable($data->checkOut),
            guestName: $data->guestName,
            note: $data->note,
        )));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        \assert(null !== $reservation);

        return ReservationOutput::fromEntity($reservation);
    }
}
