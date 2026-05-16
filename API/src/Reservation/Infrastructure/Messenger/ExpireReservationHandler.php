<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Messenger;

use App\Reservation\Application\UseCase\ExpirePendingReservation;
use App\Reservation\Domain\Command\ExpirePendingReservationCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class ExpireReservationHandler
{
    public function __construct(
        private ExpirePendingReservation $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(ExpireReservationMessage $message): void
    {
        $this->handler->execute(fn () => $this->useCase->handle(new ExpirePendingReservationCommand(
            reservationId: $message->reservationId->toRfc4122(),
            dispatchedAt: $message->dispatchedAt,
        )));
    }
}
