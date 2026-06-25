<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Reservation\Application\UseCase\RequestReservation;
use App\Reservation\Domain\Command\RequestReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Infrastructure\Security\CurrentUser;
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
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReservationOutput
    {
        if (!$data instanceof RequestReservationInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', RequestReservationInput::class, get_debug_type($data)));
        }

        /** @var string $id */
        $id = $this->handler->execute(fn () => $this->requestReservation->handle(new RequestReservationCommand(
            accommodationId: Uuid::fromString($data->accommodationId),
            guestUserId: $this->currentUser->id(),
            guestTeamId: $this->currentUser->teamId(),
            checkIn: new \DateTimeImmutable($data->checkIn),
            checkOut: new \DateTimeImmutable($data->checkOut),
            guestName: $data->guestName,
            note: $data->note,
            paymentIntentId: $data->paymentIntentId,
        )));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        if (null === $reservation) {
            throw new \RuntimeException('Reservation could not be reloaded after the operation.');
        }

        return ReservationOutput::fromEntity($reservation);
    }
}
