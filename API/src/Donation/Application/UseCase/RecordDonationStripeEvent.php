<?php

declare(strict_types=1);

namespace App\Donation\Application\UseCase;

use App\Donation\Domain\Command\RecordDonationStripeEventCommand;
use App\Donation\Domain\Port\DonationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class RecordDonationStripeEvent
{
    public const string EVENT_SUCCEEDED = 'payment_intent.succeeded';
    public const string EVENT_CANCELED = 'payment_intent.canceled';
    public const string EVENT_FAILED = 'payment_intent.payment_failed';

    public function __construct(
        private DonationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(RecordDonationStripeEventCommand $command): void
    {
        $donation = $this->repository->findByPaymentIntentId($command->paymentIntentId);

        if (null === $donation) {
            // The payment intent does not belong to a donation (e.g. a reservation
            // payment) or the local row does not exist yet. Acknowledge silently.
            return;
        }

        match ($command->eventType) {
            self::EVENT_SUCCEEDED => $donation->markPaid(),
            self::EVENT_CANCELED => $donation->markCancelled(),
            self::EVENT_FAILED => $donation->markFailed(),
            default => null,
        };

        $events = $donation->releaseEvents();
        if ([] === $events) {
            return;
        }

        $this->repository->save($donation);
        $this->eventBus->dispatch($events);
    }
}
