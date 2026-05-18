<?php

declare(strict_types=1);

namespace App\Payment\Application\Listener;

use App\Payment\Application\UseCase\CancelPaymentForReservation;
use App\Payment\Domain\Command\CancelPaymentForReservationCommand;
use App\Shared\Domain\Event\ReservationRefused;

final readonly class CancelPaymentOnReservationRefused
{
    public function __construct(private CancelPaymentForReservation $cancelPayment)
    {
    }

    public function __invoke(ReservationRefused $event): void
    {
        $this->cancelPayment->handle(new CancelPaymentForReservationCommand($event->reservationId));
    }
}
