<?php

declare(strict_types=1);

namespace App\Payment\Application\UseCase;

use App\Payment\Domain\Command\CancelPaymentForReservationCommand;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Port\PaymentGateway;
use App\Payment\Domain\Port\PaymentRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class CancelPaymentForReservation
{
    public function __construct(
        private PaymentRepository $repository,
        private PaymentGateway $gateway,
        private EventBus $eventBus,
    ) {
    }

    public function handle(CancelPaymentForReservationCommand $command): void
    {
        $payment = $this->repository->findByReservationId($command->reservationId);

        if (null === $payment) {
            // No payment recorded for this reservation: nothing to do, no error.
            return;
        }

        if (PaymentStatus::Cancelled === $payment->getStatus() || PaymentStatus::Refunded === $payment->getStatus()) {
            return;
        }

        if (PaymentStatus::Captured === $payment->getStatus()) {
            // The money already reached us: give back the share the cancellation
            // policy grants the guest, rounded down to avoid over-refunding.
            $refundCents = intdiv($payment->getAmountCents() * $command->refundPercentage, 100);
            if ($refundCents <= 0) {
                return;
            }

            $this->gateway->refund($payment->getStripePaymentIntentId(), $refundCents);
            $payment->markRefunded($refundCents);
        } else {
            $this->gateway->cancel($payment->getStripePaymentIntentId());
            $payment->markCancelled();
        }

        $this->repository->save($payment);
        $this->eventBus->dispatch($payment->releaseEvents());
    }
}
