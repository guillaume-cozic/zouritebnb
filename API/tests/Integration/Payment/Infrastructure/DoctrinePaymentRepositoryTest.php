<?php

declare(strict_types=1);

namespace App\Tests\Integration\Payment\Infrastructure;

use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Port\PaymentRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrinePaymentRepositoryTest extends RepositoryTestCase
{
    private PaymentRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(PaymentRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $reservationId = Uuid::v4();
        $createdAt = new \DateTimeImmutable('2026-01-15 10:30:00');
        $payment = new Payment(
            id: $id,
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_123456789',
            status: PaymentStatus::Pending,
            amountCents: 12050,
            currency: 'eur',
            createdAt: $createdAt,
        );

        $this->repository->save($payment);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertEquals($reservationId, $found->getReservationId());
        self::assertSame('pi_123456789', $found->getStripePaymentIntentId());
        self::assertSame(PaymentStatus::Pending, $found->getStatus());
        self::assertSame(12050, $found->getAmountCents());
        self::assertSame('eur', $found->getCurrency());
        self::assertEquals($createdAt, $found->getCreatedAt());
    }

    public function test_should_return_null_when_not_found_by_id(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_save_and_find_without_reservation(): void
    {
        $id = Uuid::v4();
        $payment = new Payment(
            id: $id,
            reservationId: null,
            stripePaymentIntentId: 'pi_no_reservation',
            status: PaymentStatus::Pending,
            amountCents: 5000,
            currency: 'usd',
            createdAt: new \DateTimeImmutable('2026-02-01 08:00:00'),
        );

        $this->repository->save($payment);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNull($found->getReservationId());
        self::assertSame('usd', $found->getCurrency());
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $createdAt = new \DateTimeImmutable('2026-03-10 12:00:00');
        $payment = new Payment(
            id: $id,
            reservationId: null,
            stripePaymentIntentId: 'pi_update',
            status: PaymentStatus::Pending,
            amountCents: 9900,
            currency: 'eur',
            createdAt: $createdAt,
        );
        $this->repository->save($payment);

        $reservationId = Uuid::v4();
        $payment->linkReservation($reservationId);
        $payment->markAuthorized();
        $this->repository->save($payment);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertEquals($reservationId, $found->getReservationId());
        self::assertSame(PaymentStatus::Authorized, $found->getStatus());
        self::assertSame('pi_update', $found->getStripePaymentIntentId());
        self::assertSame(9900, $found->getAmountCents());
    }

    public function test_should_persist_the_refunded_amount(): void
    {
        $id = Uuid::v4();
        $payment = new Payment(
            id: $id,
            reservationId: null,
            stripePaymentIntentId: 'pi_refund_roundtrip',
            status: PaymentStatus::Captured,
            amountCents: 8000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable('2026-07-01 10:00:00'),
        );
        $this->repository->save($payment);
        self::assertNull($this->repository->findById($id)->getRefundedAmountCents());

        $payment->markRefunded(4000);
        $this->repository->save($payment);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame(PaymentStatus::Refunded, $found->getStatus());
        self::assertSame(4000, $found->getRefundedAmountCents());
    }

    public function test_should_persist_each_status(): void
    {
        $statuses = [
            PaymentStatus::Pending,
            PaymentStatus::Authorized,
            PaymentStatus::Captured,
            PaymentStatus::Cancelled,
            PaymentStatus::Refunded,
            PaymentStatus::Failed,
        ];

        foreach ($statuses as $index => $status) {
            $id = Uuid::v4();
            $payment = new Payment(
                id: $id,
                reservationId: null,
                stripePaymentIntentId: 'pi_status_'.$index,
                status: $status,
                amountCents: 1000 + $index,
                currency: 'eur',
                createdAt: new \DateTimeImmutable('2026-04-01 00:00:00'),
            );
            $this->repository->save($payment);

            $found = $this->repository->findById($id);
            self::assertNotNull($found);
            self::assertSame($status, $found->getStatus());
        }
    }

    public function test_should_find_by_payment_intent_id(): void
    {
        $id = Uuid::v4();
        $payment = new Payment(
            id: $id,
            reservationId: null,
            stripePaymentIntentId: 'pi_intent_lookup',
            status: PaymentStatus::Captured,
            amountCents: 4200,
            currency: 'gbp',
            createdAt: new \DateTimeImmutable('2026-05-20 14:45:00'),
        );
        $this->repository->save($payment);

        $found = $this->repository->findByPaymentIntentId('pi_intent_lookup');

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('pi_intent_lookup', $found->getStripePaymentIntentId());
        self::assertSame(PaymentStatus::Captured, $found->getStatus());
    }

    public function test_should_return_null_when_payment_intent_id_not_found(): void
    {
        $result = $this->repository->findByPaymentIntentId('pi_does_not_exist');

        self::assertNull($result);
    }

    public function test_should_find_by_reservation_id(): void
    {
        $id = Uuid::v4();
        $reservationId = Uuid::v4();
        $payment = new Payment(
            id: $id,
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_reservation_lookup',
            status: PaymentStatus::Authorized,
            amountCents: 7800,
            currency: 'eur',
            createdAt: new \DateTimeImmutable('2026-06-07 09:15:00'),
        );
        $this->repository->save($payment);

        $found = $this->repository->findByReservationId($reservationId);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertEquals($reservationId, $found->getReservationId());
        self::assertSame('pi_reservation_lookup', $found->getStripePaymentIntentId());
    }

    public function test_should_return_null_when_reservation_id_not_found(): void
    {
        $result = $this->repository->findByReservationId(Uuid::v4());

        self::assertNull($result);
    }
}
