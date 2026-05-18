<?php

declare(strict_types=1);

namespace App\Payment\Application\UseCase;

use App\Payment\Domain\Command\RecordStripeEventCommand;
use App\Payment\Domain\Port\PaymentRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class RecordStripeEvent
{
    public const string EVENT_SUCCEEDED = 'payment_intent.succeeded';
    public const string EVENT_CANCELED = 'payment_intent.canceled';
    public const string EVENT_FAILED = 'payment_intent.payment_failed';
    public const string EVENT_AUTHORIZED = 'payment_intent.amount_capturable_updated';

    public function __construct(
        private PaymentRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(RecordStripeEventCommand $command): void
    {
        $payment = $this->repository->findByPaymentIntentId($command->paymentIntentId);

        if (null === $payment) {
            // Webhook can arrive before the local Payment row exists for transient races.
            // Acknowledge silently — Stripe will retry, and the next call will find the row.
            return;
        }

        match ($command->eventType) {
            self::EVENT_SUCCEEDED => $payment->markCaptured(),
            self::EVENT_CANCELED => $payment->markCancelled(),
            self::EVENT_FAILED => $payment->markFailed(),
            self::EVENT_AUTHORIZED => $payment->markAuthorized(),
            default => null,
        };

        $events = $payment->releaseEvents();
        if ([] === $events) {
            return;
        }

        $this->repository->save($payment);
        $this->eventBus->dispatch($events);
    }
}
