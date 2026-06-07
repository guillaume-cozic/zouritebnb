<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Domain\Entity;

use App\Team\Domain\Entity\InvitationStatus;
use PHPUnit\Framework\TestCase;

final class InvitationStatusTest extends TestCase
{
    public function test_should_expose_expected_string_values(): void
    {
        self::assertSame('pending', InvitationStatus::Pending->value);
        self::assertSame('accepted', InvitationStatus::Accepted->value);
        self::assertSame('cancelled', InvitationStatus::Cancelled->value);
    }

    public function test_should_build_from_string_value(): void
    {
        self::assertSame(InvitationStatus::Pending, InvitationStatus::from('pending'));
        self::assertSame(InvitationStatus::Accepted, InvitationStatus::from('accepted'));
        self::assertSame(InvitationStatus::Cancelled, InvitationStatus::from('cancelled'));
    }

    public function test_should_return_null_when_trying_from_unknown_value(): void
    {
        self::assertNull(InvitationStatus::tryFrom('unknown'));
    }

    public function test_should_list_all_cases(): void
    {
        self::assertSame(
            [InvitationStatus::Pending, InvitationStatus::Accepted, InvitationStatus::Cancelled],
            InvitationStatus::cases(),
        );
    }
}
