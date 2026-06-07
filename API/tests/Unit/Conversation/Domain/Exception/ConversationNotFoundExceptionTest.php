<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Domain\Exception;

use App\Conversation\Domain\Exception\ConversationNotFoundException;
use PHPUnit\Framework\TestCase;

final class ConversationNotFoundExceptionTest extends TestCase
{
    public function test_should_build_exception_from_id(): void
    {
        $exception = ConversationNotFoundException::becauseId('abc-123');

        self::assertInstanceOf(ConversationNotFoundException::class, $exception);
        self::assertSame('Conversation "abc-123" not found.', $exception->getMessage());
    }

    public function test_should_build_exception_from_reservation_id(): void
    {
        $exception = ConversationNotFoundException::becauseReservationId('res-456');

        self::assertInstanceOf(ConversationNotFoundException::class, $exception);
        self::assertSame('No conversation found for reservation "res-456".', $exception->getMessage());
    }
}
