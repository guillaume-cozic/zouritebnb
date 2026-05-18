<?php

declare(strict_types=1);

namespace App\Payment\Application\UseCase;

use App\Payment\Domain\Command\LinkPaymentToReservationCommand;
use App\Payment\Domain\Exception\PaymentNotFoundException;
use App\Payment\Domain\Port\PaymentRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class LinkPaymentToReservation
{
    public function __construct(
        private PaymentRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(LinkPaymentToReservationCommand $command): void
    {
        $payment = $this->repository->findByPaymentIntentId($command->paymentIntentId);

        if (null === $payment) {
            throw PaymentNotFoundException::becausePaymentIntentId($command->paymentIntentId);
        }

        $payment->linkReservation($command->reservationId);

        $this->repository->save($payment);
        $this->eventBus->dispatch($payment->releaseEvents());
    }
}
