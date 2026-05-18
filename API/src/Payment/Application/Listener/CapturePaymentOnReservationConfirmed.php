<?php

declare(strict_types=1);

namespace App\Payment\Application\Listener;

use App\Payment\Application\UseCase\CapturePaymentForReservation;
use App\Payment\Domain\Command\CapturePaymentForReservationCommand;
use App\Payment\Domain\Exception\PaymentNotFoundException;
use App\Shared\Domain\Event\ReservationConfirmed;

final readonly class CapturePaymentOnReservationConfirmed
{
    public function __construct(private CapturePaymentForReservation $capturePayment)
    {
    }

    public function __invoke(ReservationConfirmed $event): void
    {
        try {
            $this->capturePayment->handle(new CapturePaymentForReservationCommand($event->reservationId));
        } catch (PaymentNotFoundException) {
            // Reservation has no payment recorded (e.g. legacy data) — ignore.
        }
    }
}
