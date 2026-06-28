<?php

declare(strict_types=1);

namespace App\Payment\Application\UseCase;

use App\Payment\Domain\Command\CreatePaymentIntentCommand;
use App\Payment\Domain\Entity\CreatedPaymentIntent;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Exception\InvalidPaymentException;
use App\Payment\Domain\Port\PaymentGateway;
use App\Payment\Domain\Port\PaymentRepository;
use App\Shared\Domain\Port\AccommodationPricingProvider;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;
use App\Shared\Domain\Service\StayPriceCalculator;

final readonly class CreatePaymentIntent
{
    /** The marketplace operates in euros only; the currency is never taken from the client. */
    private const string CURRENCY = 'eur';

    public function __construct(
        private PaymentRepository $repository,
        private PaymentGateway $gateway,
        private EventBus $eventBus,
        private Clock $clock,
        private AccommodationPricingProvider $pricingProvider,
        private StayPriceCalculator $priceCalculator,
    ) {
    }

    public function handle(CreatePaymentIntentCommand $command): CreatedPaymentIntent
    {
        // The amount is derived server-side from the accommodation's pricing and
        // the requested dates — never trusted from the client.
        $pricing = $this->pricingProvider->findByAccommodationId($command->accommodationId);
        if (null === $pricing) {
            throw InvalidPaymentException::becauseAccommodationNotFound($command->accommodationId->toRfc4122());
        }

        $amountCents = $this->priceCalculator
            ->calculate($pricing, $command->checkIn, $command->checkOut, $this->clock->now())
            ->amountInCents();

        // Reject degenerate bookings (e.g. zero nights) before reaching the gateway.
        if ($amountCents <= 0) {
            throw InvalidPaymentException::becauseAmountIsNotPositive($amountCents);
        }

        $authorization = $this->gateway->createAuthorization(
            amountCents: $amountCents,
            currency: self::CURRENCY,
            description: \sprintf('Réservation %s', $command->accommodationId->toRfc4122()),
            metadata: [
                'accommodationId' => $command->accommodationId->toRfc4122(),
                'userId' => $command->userId->toRfc4122(),
                'checkIn' => $command->checkIn->format(\DateTimeInterface::ATOM),
                'checkOut' => $command->checkOut->format(\DateTimeInterface::ATOM),
            ],
        );

        $payment = new Payment(
            id: UuidGenerator::generate(),
            reservationId: null,
            stripePaymentIntentId: $authorization->paymentIntentId,
            status: PaymentStatus::Pending,
            amountCents: $amountCents,
            currency: self::CURRENCY,
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
