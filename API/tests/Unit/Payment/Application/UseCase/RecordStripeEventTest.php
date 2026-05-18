<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\UseCase;

use App\Payment\Application\UseCase\RecordStripeEvent;
use App\Payment\Domain\Command\RecordStripeEventCommand;
use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RecordStripeEventTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private InMemoryEventBus $eventBus;
    private RecordStripeEvent $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new RecordStripeEvent($this->repository, $this->eventBus);
    }

    public function testShouldMarkPaymentCapturedOnSucceededEvent(): void
    {
        $this->repository->save($this->makePayment('pi_test_1', PaymentStatus::Authorized));

        $this->useCase->handle(new RecordStripeEventCommand(RecordStripeEvent::EVENT_SUCCEEDED, 'pi_test_1'));

        self::assertSame(PaymentStatus::Captured, $this->repository->findByPaymentIntentId('pi_test_1')->getStatus());
    }

    public function testShouldMarkPaymentCancelledOnCanceledEvent(): void
    {
        $this->repository->save($this->makePayment('pi_test_2', PaymentStatus::Pending));

        $this->useCase->handle(new RecordStripeEventCommand(RecordStripeEvent::EVENT_CANCELED, 'pi_test_2'));

        self::assertSame(PaymentStatus::Cancelled, $this->repository->findByPaymentIntentId('pi_test_2')->getStatus());
    }

    public function testShouldMarkPaymentFailedOnPaymentFailedEvent(): void
    {
        $this->repository->save($this->makePayment('pi_test_3', PaymentStatus::Pending));

        $this->useCase->handle(new RecordStripeEventCommand(RecordStripeEvent::EVENT_FAILED, 'pi_test_3'));

        self::assertSame(PaymentStatus::Failed, $this->repository->findByPaymentIntentId('pi_test_3')->getStatus());
    }

    public function testShouldNoopWhenPaymentIntentIdUnknown(): void
    {
        $this->useCase->handle(new RecordStripeEventCommand(RecordStripeEvent::EVENT_SUCCEEDED, 'pi_unknown'));

        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    public function testShouldIgnoreUnknownEventTypes(): void
    {
        $payment = $this->makePayment('pi_test_x', PaymentStatus::Pending);
        $this->repository->save($payment);

        $this->useCase->handle(new RecordStripeEventCommand('payment_intent.created', 'pi_test_x'));

        self::assertSame(PaymentStatus::Pending, $this->repository->findByPaymentIntentId('pi_test_x')->getStatus());
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    private function makePayment(string $paymentIntentId, PaymentStatus $status): Payment
    {
        return new Payment(
            id: Uuid::v7(),
            reservationId: null,
            stripePaymentIntentId: $paymentIntentId,
            status: $status,
            amountCents: 1000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
    }
}
