<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Domain\Entity;

use App\Payment\Domain\Entity\PaymentStatus;
use PHPUnit\Framework\TestCase;

final class PaymentStatusTest extends TestCase
{
    public function test_should_expose_all_expected_cases(): void
    {
        $values = array_map(
            static fn (PaymentStatus $status): string => $status->value,
            PaymentStatus::cases(),
        );

        self::assertSame(
            ['pending', 'authorized', 'captured', 'cancelled', 'refunded', 'failed'],
            $values,
        );
    }

    public function test_should_build_from_value(): void
    {
        self::assertSame(PaymentStatus::Pending, PaymentStatus::from('pending'));
        self::assertSame(PaymentStatus::Authorized, PaymentStatus::from('authorized'));
        self::assertSame(PaymentStatus::Captured, PaymentStatus::from('captured'));
        self::assertSame(PaymentStatus::Cancelled, PaymentStatus::from('cancelled'));
        self::assertSame(PaymentStatus::Refunded, PaymentStatus::from('refunded'));
        self::assertSame(PaymentStatus::Failed, PaymentStatus::from('failed'));
    }

    public function test_should_return_null_from_invalid_value_with_try_from(): void
    {
        self::assertNull(PaymentStatus::tryFrom('unknown'));
    }
}
