<?php

declare(strict_types=1);

namespace App\Payment\Application\Listener;

use App\Payment\Application\UseCase\CancelPaymentForReservation;
use App\Payment\Domain\Command\CancelPaymentForReservationCommand;
use App\Shared\Domain\Event\ReservationCancelled;

final readonly class CancelPaymentOnReservationCancelled
{
    public function __construct(private CancelPaymentForReservation $cancelPayment)
    {
    }

    public function __invoke(ReservationCancelled $event): void
    {
        $this->cancelPayment->handle(new CancelPaymentForReservationCommand(
            reservationId: $event->reservationId,
            refundPercentage: $event->refundPercentage,
        ));
    }
}
