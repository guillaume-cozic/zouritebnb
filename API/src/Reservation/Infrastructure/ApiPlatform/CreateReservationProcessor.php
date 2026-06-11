<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\CreateReservation;
use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<ReservationInput, ReservationOutput>
 */
final readonly class CreateReservationProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateReservation $createReservation,
        private ReservationRepository $repository,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReservationOutput
    {
        if (!$data instanceof ReservationInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', ReservationInput::class, get_debug_type($data)));
        }

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->createReservation->handle(new CreateReservationCommand(
            accommodationId: Uuid::fromString($data->accommodationId),
            teamId: $this->currentUser->teamId(),
            checkIn: new \DateTimeImmutable($data->checkIn),
            checkOut: new \DateTimeImmutable($data->checkOut),
            guestName: $data->guestName,
        )));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        if (null === $reservation) {
            throw new \RuntimeException('Reservation could not be reloaded after the operation.');
        }

        return ReservationOutput::fromEntity($reservation);
    }
}
