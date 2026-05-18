<?php

declare(strict_types=1);

namespace App\Payment\Application\UseCase;

use App\Payment\Domain\Command\CreatePaymentIntentCommand;
use App\Payment\Domain\Entity\CreatedPaymentIntent;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Port\PaymentGateway;
use App\Payment\Domain\Port\PaymentRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class CreatePaymentIntent
{
    public function __construct(
        private PaymentRepository $repository,
        private PaymentGateway $gateway,
        private EventBus $eventBus,
        private Clock $clock,
    ) {
    }

    public function handle(CreatePaymentIntentCommand $command): CreatedPaymentIntent
    {
        $authorization = $this->gateway->createAuthorization(
            amountCents: $command->amountCents,
            currency: $command->currency,
            description: $command->description,
            metadata: $command->metadata,
        );

        $payment = new Payment(
            id: UuidGenerator::generate(),
            reservationId: null,
            stripePaymentIntentId: $authorization->paymentIntentId,
            status: PaymentStatus::Pending,
            amountCents: $command->amountCents,
            currency: $command->currency,
            createdAt: $this->clock->now(),
        );

        $this->repository->save($payment);
        $this->eventBus->dispatch($payment->releaseEvents());

        return new CreatedPaymentIntent(
            paymentIntentId: $authorization->paymentIntentId,
            clientSecret: $authorization->clientSecret,
        );
    }
}
