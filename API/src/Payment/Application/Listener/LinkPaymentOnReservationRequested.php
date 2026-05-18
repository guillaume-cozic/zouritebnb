<?php

declare(strict_types=1);

namespace App\Payment\Application\Listener;

use App\Payment\Application\UseCase\LinkPaymentToReservation;
use App\Payment\Domain\Command\LinkPaymentToReservationCommand;
use App\Payment\Domain\Exception\PaymentNotFoundException;
use App\Shared\Domain\Event\ReservationRequested;

/**
 * Reads the {@see ReservationRequested::$paymentIntentId} field (added in a sibling
 * Reservation module change) and persists the link to the local Payment row.
 *
 * If the event does not carry a payment intent id (legacy or off-platform flow), the
 * listener silently no-ops.
 */
final readonly class LinkPaymentOnReservationRequested
{
    public function __construct(private LinkPaymentToReservation $linkPaymentToReservation)
    {
    }

    public function __invoke(ReservationRequested $event): void
    {
        $paymentIntentId = $event->paymentIntentId;

        if (null === $paymentIntentId || '' === $paymentIntentId) {
            return;
        }

        try {
            $this->linkPaymentToReservation->handle(new LinkPaymentToReservationCommand(
                paymentIntentId: $paymentIntentId,
                reservationId: $event->reservationId,
            ));
        } catch (PaymentNotFoundException) {
            // Payment row not found (e.g. webhook hasn't created it yet) — ignore;
            // the webhook flow will reconcile on its own.
        }
    }
}
