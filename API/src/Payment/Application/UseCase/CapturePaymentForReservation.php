<?php

declare(strict_types=1);

namespace App\Payment\Application\UseCase;

use App\Payment\Domain\Command\CapturePaymentForReservationCommand;
use App\Payment\Domain\Exception\PaymentNotFoundException;
use App\Payment\Domain\Port\PaymentGateway;
use App\Payment\Domain\Port\PaymentRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class CapturePaymentForReservation
{
    public function __construct(
        private PaymentRepository $repository,
        private PaymentGateway $gateway,
        private EventBus $eventBus,
    ) {
    }

    public function handle(CapturePaymentForReservationCommand $command): void
    {
        $payment = $this->repository->findByReservationId($command->reservationId);

        if (null === $payment) {
            throw PaymentNotFoundException::becauseReservationId($command->reservationId->toRfc4122());
        }

        $this->gateway->capture($payment->getStripePaymentIntentId());

        $payment->markCaptured();

        $this->repository->save($payment);
        $this->eventBus->dispatch($payment->releaseEvents());
    }
}
