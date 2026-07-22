<?php

declare(strict_types=1);

namespace App\Donation\Application\UseCase;

use App\Donation\Domain\Command\CreateDonationIntentCommand;
use App\Donation\Domain\Entity\CreatedDonationIntent;
use App\Donation\Domain\Entity\Donation;
use App\Donation\Domain\Entity\DonationStatus;
use App\Donation\Domain\Exception\InvalidDonationException;
use App\Donation\Domain\Port\DonationGateway;
use App\Donation\Domain\Port\DonationRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\SolidarityProjectDonationChecker;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class CreateDonationIntent
{
    /** The marketplace operates in euros only; the currency is never taken from the client. */
    private const string CURRENCY = 'eur';

    public function __construct(
        private DonationRepository $repository,
        private DonationGateway $gateway,
        private EventBus $eventBus,
        private Clock $clock,
        private SolidarityProjectDonationChecker $projectChecker,
    ) {
    }

    public function handle(CreateDonationIntentCommand $command): CreatedDonationIntent
    {
        if (!$this->projectChecker->isActive($command->solidarityProjectId)) {
            throw InvalidDonationException::becauseSolidarityProjectNotDonatable($command->solidarityProjectId->toRfc4122());
        }

        // The amount is freely chosen by the donor (it is a donation, not a price).
        // The entity constructor needs the payment intent id, so the gateway is
        // called first — validate the amount bounds beforehand to avoid creating
        // a Stripe intent for an amount the domain would reject.
        Donation::ensureAmountWithinBounds($command->amountCents);

        $payment = $this->gateway->createPayment(
            amountCents: $command->amountCents,
            currency: self::CURRENCY,
            description: \sprintf('Don projet solidaire %s', $command->solidarityProjectId->toRfc4122()),
            metadata: [
                'solidarityProjectId' => $command->solidarityProjectId->toRfc4122(),
                'type' => 'donation',
            ],
        );

        $donation = new Donation(
            id: UuidGenerator::generate(),
            solidarityProjectId: $command->solidarityProjectId,
            stripePaymentIntentId: $payment->paymentIntentId,
            status: DonationStatus::Pending,
            amountCents: $command->amountCents,
            currency: self::CURRENCY,
            createdAt: $this->clock->now(),
        );

        $this->repository->save($donation);
        $this->eventBus->dispatch($donation->releaseEvents());

        return new CreatedDonationIntent(
            paymentIntentId: $payment->paymentIntentId,
            clientSecret: $payment->clientSecret,
        );
    }
}
