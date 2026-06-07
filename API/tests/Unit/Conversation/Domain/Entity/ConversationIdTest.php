<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Domain\Entity;

use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Exception\InvalidConversationIdException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ConversationIdTest extends TestCase
{
    public function test_should_expose_underlying_uuid(): void
    {
        $uuid = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $id = new ConversationId($uuid);

        self::assertSame($uuid, $id->toUuid());
    }

    public function test_should_expose_rfc4122_string(): void
    {
        $uuid = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $id = new ConversationId($uuid);

        self::assertSame($uuid->toRfc4122(), $id->toString());
        self::assertSame('01961e2f-dead-7000-beef-000000000001', $id->toString());
    }

    public function test_should_throw_when_value_is_null(): void
    {
        $this->expectException(InvalidConversationIdException::class);
        $this->expectExceptionMessage('Conversation id is required.');

        new ConversationId(null);
    }
}
