<?php

declare(strict_types=1);

namespace App\Tests\Unit\Donation\Application\UseCase;

use App\Donation\Application\UseCase\RecordDonationStripeEvent;
use App\Donation\Domain\Command\RecordDonationStripeEventCommand;
use App\Donation\Domain\Entity\Donation;
use App\Donation\Domain\Entity\DonationStatus;
use App\Donation\Domain\Event\DonationCancelled;
use App\Donation\Domain\Event\DonationFailed;
use App\Donation\Domain\Event\DonationPaid;
use App\Tests\Unit\Donation\Infrastructure\InMemoryDonationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RecordDonationStripeEventTest extends TestCase
{
    private InMemoryDonationRepository $repository;
    private InMemoryEventBus $eventBus;
    private RecordDonationStripeEvent $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryDonationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new RecordDonationStripeEvent($this->repository, $this->eventBus);
    }

    public function test_should_mark_donation_paid_on_succeeded_event(): void
    {
        $this->repository->save($this->makeDonation('pi_donation_1'));

        $this->useCase->handle(new RecordDonationStripeEventCommand(RecordDonationStripeEvent::EVENT_SUCCEEDED, 'pi_donation_1'));

        self::assertSame(DonationStatus::Paid, $this->repository->findByPaymentIntentId('pi_donation_1')->getStatus());
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(DonationPaid::class, $events[0]);
        self::assertSame('pi_donation_1', $events[0]->stripePaymentIntentId);
    }

    public function test_should_mark_donation_failed_on_payment_failed_event(): void
    {
        $this->repository->save($this->makeDonation('pi_donation_2'));

        $this->useCase->handle(new RecordDonationStripeEventCommand(RecordDonationStripeEvent::EVENT_FAILED, 'pi_donation_2'));

        self::assertSame(DonationStatus::Failed, $this->repository->findByPaymentIntentId('pi_donation_2')->getStatus());
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(DonationFailed::class, $events[0]);
    }

    public function test_should_mark_donation_cancelled_on_canceled_event(): void
    {
        $this->repository->save($this->makeDonation('pi_donation_3'));

        $this->useCase->handle(new RecordDonationStripeEventCommand(RecordDonationStripeEvent::EVENT_CANCELED, 'pi_donation_3'));

        self::assertSame(DonationStatus::Cancelled, $this->repository->findByPaymentIntentId('pi_donation_3')->getStatus());
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(DonationCancelled::class, $events[0]);
    }

    public function test_should_noop_when_payment_intent_id_unknown(): void
    {
        $this->useCase->handle(new RecordDonationStripeEventCommand(RecordDonationStripeEvent::EVENT_SUCCEEDED, 'pi_unknown'));

        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    public function test_should_ignore_unknown_event_types(): void
    {
        $this->repository->save($this->makeDonation('pi_donation_x'));

        $this->useCase->handle(new RecordDonationStripeEventCommand('payment_intent.created', 'pi_donation_x'));

        self::assertSame(DonationStatus::Pending, $this->repository->findByPaymentIntentId('pi_donation_x')->getStatus());
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    private function makeDonation(string $paymentIntentId, DonationStatus $status = DonationStatus::Pending): Donation
    {
        return new Donation(
            id: Uuid::v7(),
            solidarityProjectId: Uuid::v7(),
            stripePaymentIntentId: $paymentIntentId,
            status: $status,
            amountCents: 2500,
            currency: 'eur',
            createdAt: new \DateTimeImmutable(),
        );
    }
}
