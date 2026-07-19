<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Domain\Entity;

use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Event\PaymentAuthorized;
use App\Payment\Domain\Event\PaymentCancelled;
use App\Payment\Domain\Event\PaymentCaptured;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\PaymentLinkedToReservation;
use App\Payment\Domain\Event\PaymentRefunded;
use App\Payment\Domain\Exception\InvalidPaymentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PaymentTest extends TestCase
{
    private const PAYMENT_ID = '01961e2f-dead-7000-beef-000000000001';
    private const RESERVATION_ID = '01961e2f-dead-7000-beef-000000000002';

    public function test_should_create_a_valid_payment(): void
    {
        $id = Uuid::fromString(self::PAYMENT_ID);
        $reservationId = Uuid::fromString(self::RESERVATION_ID);
        $createdAt = new \DateTimeImmutable('2026-04-13T10:00:00+00:00');

        $payment = new Payment(
            id: $id,
            reservationId: $reservationId,
            stripePaymentIntentId: 'pi_123',
            status: PaymentStatus::Pending,
            amountCents: 5000,
            currency: 'eur',
            createdAt: $createdAt,
        );

        self::assertSame($id, $payment->getId());
        self::assertSame($reservationId, $payment->getReservationId());
        self::assertSame('pi_123', $payment->getStripePaymentIntentId());
        self::assertSame(PaymentStatus::Pending, $payment->getStatus());
        self::assertSame(5000, $payment->getAmountCents());
        self::assertSame('eur', $payment->getCurrency());
        self::assertSame($createdAt, $payment->getCreatedAt());
        self::assertSame([], $payment->releaseEvents());
    }

    public function test_should_accept_null_reservation_id(): void
    {
        $payment = $this->payment(reservationId: null);

        self::assertNull($payment->getReservationId());
    }

    public function test_should_trim_stripe_payment_intent_id(): void
    {
        $payment = $this->payment(stripePaymentIntentId: '  pi_123  ');

        self::assertSame('pi_123', $payment->getStripePaymentIntentId());
    }

    public function test_should_lowercase_currency(): void
    {
        $payment = $this->payment(currency: 'EUR');

        self::assertSame('eur', $payment->getCurrency());
    }

    public function test_should_throw_when_stripe_payment_intent_id_is_blank(): void
    {
        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('Payment intent identifier must not be blank.');

        $this->payment(stripePaymentIntentId: '   ');
    }

    #[DataProvider('nonPositiveAmounts')]
    public function test_should_throw_when_amount_is_not_positive(int $amountCents): void
    {
        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage(\sprintf('Payment amount must be greater than zero, got %d cents.', $amountCents));

        $this->payment(amountCents: $amountCents);
    }

    public static function nonPositiveAmounts(): \Generator
    {
        yield 'zero' => [0];
        yield 'negative' => [-100];
    }

    #[DataProvider('invalidCurrencies')]
    public function test_should_throw_when_currency_is_invalid(string $currency, string $normalized): void
    {
        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage(\sprintf('Payment currency must be a 3-letter ISO code, got "%s".', $normalized));

        $this->payment(currency: $currency);
    }

    public static function invalidCurrencies(): \Generator
    {
        yield 'too short' => ['eu', 'eu'];
        yield 'too long' => ['euro', 'euro'];
        yield 'contains digit' => ['eu1', 'eu1'];
        yield 'empty' => ['', ''];
    }

    public function test_should_mark_authorized_from_pending(): void
    {
        $payment = $this->payment(status: PaymentStatus::Pending, stripePaymentIntentId: 'pi_auth');

        $payment->markAuthorized();

        self::assertSame(PaymentStatus::Authorized, $payment->getStatus());
        $events = $payment->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentAuthorized::class, $events[0]);
        self::assertTrue($payment->getId()->equals($events[0]->paymentId));
        self::assertSame('pi_auth', $events[0]->stripePaymentIntentId);
    }

    public function test_should_be_idempotent_when_already_authorized(): void
    {
        $payment = $this->payment(status: PaymentStatus::Authorized);

        $payment->markAuthorized();

        self::assertSame(PaymentStatus::Authorized, $payment->getStatus());
        self::assertSame([], $payment->releaseEvents());
    }

    public function test_should_throw_when_authorizing_from_invalid_status(): void
    {
        $payment = $this->payment(status: PaymentStatus::Captured);

        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('Cannot transition payment from "captured" to "authorized".');

        $payment->markAuthorized();
    }

    public function test_should_mark_captured_from_pending(): void
    {
        $payment = $this->payment(status: PaymentStatus::Pending);

        $payment->markCaptured();

        self::assertSame(PaymentStatus::Captured, $payment->getStatus());
        $events = $payment->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentCaptured::class, $events[0]);
    }

    public function test_should_mark_captured_from_authorized(): void
    {
        $payment = $this->payment(status: PaymentStatus::Authorized);

        $payment->markCaptured();

        self::assertSame(PaymentStatus::Captured, $payment->getStatus());
    }

    public function test_should_be_idempotent_when_already_captured(): void
    {
        $payment = $this->payment(status: PaymentStatus::Captured);

        $payment->markCaptured();

        self::assertSame(PaymentStatus::Captured, $payment->getStatus());
        self::assertSame([], $payment->releaseEvents());
    }

    public function test_should_throw_when_capturing_from_invalid_status(): void
    {
        $payment = $this->payment(status: PaymentStatus::Cancelled);

        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('Cannot transition payment from "cancelled" to "captured".');

        $payment->markCaptured();
    }

    #[DataProvider('cancellableStatuses')]
    public function test_should_mark_cancelled(PaymentStatus $from): void
    {
        $payment = $this->payment(status: $from);

        $payment->markCancelled();

        self::assertSame(PaymentStatus::Cancelled, $payment->getStatus());
        $events = $payment->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentCancelled::class, $events[0]);
    }

    public static function cancellableStatuses(): \Generator
    {
        yield 'from pending' => [PaymentStatus::Pending];
        yield 'from authorized' => [PaymentStatus::Authorized];
        yield 'from failed' => [PaymentStatus::Failed];
    }

    public function test_should_be_idempotent_when_already_cancelled(): void
    {
        $payment = $this->payment(status: PaymentStatus::Cancelled);

        $payment->markCancelled();

        self::assertSame(PaymentStatus::Cancelled, $payment->getStatus());
        self::assertSame([], $payment->releaseEvents());
    }

    public function test_should_throw_when_cancelling_from_invalid_status(): void
    {
        $payment = $this->payment(status: PaymentStatus::Captured);

        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('Cannot transition payment from "captured" to "cancelled".');

        $payment->markCancelled();
    }

    public function test_should_mark_refunded_from_captured(): void
    {
        $payment = $this->payment(status: PaymentStatus::Captured, stripePaymentIntentId: 'pi_refund');

        $payment->markRefunded(2500);

        self::assertSame(PaymentStatus::Refunded, $payment->getStatus());
        self::assertSame(2500, $payment->getRefundedAmountCents());
        $events = $payment->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentRefunded::class, $events[0]);
        self::assertTrue($payment->getId()->equals($events[0]->paymentId));
        self::assertSame('pi_refund', $events[0]->stripePaymentIntentId);
        self::assertSame(2500, $events[0]->refundedAmountCents);
    }

    public function test_should_be_idempotent_when_already_refunded(): void
    {
        $payment = $this->payment(status: PaymentStatus::Refunded);

        $payment->markRefunded(2500);

        self::assertSame(PaymentStatus::Refunded, $payment->getStatus());
        self::assertSame([], $payment->releaseEvents());
    }

    public function test_should_throw_when_refunding_from_invalid_status(): void
    {
        $payment = $this->payment(status: PaymentStatus::Authorized);

        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('Cannot transition payment from "authorized" to "refunded".');

        $payment->markRefunded(2500);
    }

    #[DataProvider('invalidRefundAmounts')]
    public function test_should_throw_when_refund_amount_is_invalid(int $refundedAmountCents): void
    {
        $payment = $this->payment(status: PaymentStatus::Captured, amountCents: 5000);

        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage(\sprintf('Refund amount must be between 1 and 5000 cents, got %d cents.', $refundedAmountCents));

        $payment->markRefunded($refundedAmountCents);
    }

    public static function invalidRefundAmounts(): \Generator
    {
        yield 'zero' => [0];
        yield 'negative' => [-100];
        yield 'more than captured' => [5001];
    }

    #[DataProvider('failableStatuses')]
    public function test_should_mark_failed(PaymentStatus $from): void
    {
        $payment = $this->payment(status: $from);

        $payment->markFailed();

        self::assertSame(PaymentStatus::Failed, $payment->getStatus());
        $events = $payment->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentFailed::class, $events[0]);
    }

    public static function failableStatuses(): \Generator
    {
        yield 'from pending' => [PaymentStatus::Pending];
        yield 'from authorized' => [PaymentStatus::Authorized];
    }

    public function test_should_be_idempotent_when_already_failed(): void
    {
        $payment = $this->payment(status: PaymentStatus::Failed);

        $payment->markFailed();

        self::assertSame(PaymentStatus::Failed, $payment->getStatus());
        self::assertSame([], $payment->releaseEvents());
    }

    public function test_should_throw_when_failing_from_invalid_status(): void
    {
        $payment = $this->payment(status: PaymentStatus::Captured);

        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('Cannot transition payment from "captured" to "failed".');

        $payment->markFailed();
    }

    public function test_should_link_reservation_when_none_previously_set(): void
    {
        $payment = $this->payment(reservationId: null);
        $reservationId = Uuid::fromString(self::RESERVATION_ID);

        $payment->linkReservation($reservationId);

        self::assertSame($reservationId, $payment->getReservationId());
        $events = $payment->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentLinkedToReservation::class, $events[0]);
        self::assertTrue($payment->getId()->equals($events[0]->paymentId));
        self::assertTrue($reservationId->equals($events[0]->reservationId));
    }

    public function test_should_relink_reservation_to_a_different_reservation(): void
    {
        $payment = $this->payment(reservationId: Uuid::fromString(self::RESERVATION_ID));
        $newReservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $payment->linkReservation($newReservationId);

        self::assertSame($newReservationId, $payment->getReservationId());
        $events = $payment->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentLinkedToReservation::class, $events[0]);
    }

    public function test_should_be_idempotent_when_linking_same_reservation(): void
    {
        $reservationId = Uuid::fromString(self::RESERVATION_ID);
        $payment = $this->payment(reservationId: $reservationId);

        $payment->linkReservation(Uuid::fromString(self::RESERVATION_ID));

        self::assertSame($reservationId, $payment->getReservationId());
        self::assertSame([], $payment->releaseEvents());
    }

    private function payment(
        ?Uuid $reservationId = null,
        string $stripePaymentIntentId = 'pi_123',
        PaymentStatus $status = PaymentStatus::Pending,
        int $amountCents = 5000,
        string $currency = 'eur',
    ): Payment {
        return new Payment(
            id: Uuid::fromString(self::PAYMENT_ID),
            reservationId: $reservationId,
            stripePaymentIntentId: $stripePaymentIntentId,
            status: $status,
            amountCents: $amountCents,
            currency: $currency,
            createdAt: new \DateTimeImmutable('2026-04-13T10:00:00+00:00'),
        );
    }
}
