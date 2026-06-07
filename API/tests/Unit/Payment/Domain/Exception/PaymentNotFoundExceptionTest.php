<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Domain\Exception;

use App\Payment\Domain\Exception\PaymentNotFoundException;
use PHPUnit\Framework\TestCase;

final class PaymentNotFoundExceptionTest extends TestCase
{
    public function test_should_build_from_payment_id(): void
    {
        $exception = PaymentNotFoundException::becauseId('pay-123');

        self::assertInstanceOf(PaymentNotFoundException::class, $exception);
        self::assertSame('Payment "pay-123" not found.', $exception->getMessage());
    }

    public function test_should_build_from_payment_intent_id(): void
    {
        $exception = PaymentNotFoundException::becausePaymentIntentId('pi_123');

        self::assertSame('Payment with intent id "pi_123" not found.', $exception->getMessage());
    }

    public function test_should_build_from_reservation_id(): void
    {
        $exception = PaymentNotFoundException::becauseReservationId('res-123');

        self::assertSame('Payment for reservation "res-123" not found.', $exception->getMessage());
    }
}
