<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Domain\Entity;

use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Exception\InvalidMessageException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class MessageIdTest extends TestCase
{
    public function test_should_expose_underlying_uuid(): void
    {
        $uuid = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');

        $id = new MessageId($uuid);

        self::assertSame($uuid, $id->toUuid());
    }

    public function test_should_expose_rfc4122_string(): void
    {
        $uuid = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');

        $id = new MessageId($uuid);

        self::assertSame($uuid->toRfc4122(), $id->toString());
        self::assertSame('01961e2f-dead-7000-beef-000000000010', $id->toString());
    }

    public function test_should_throw_when_value_is_null(): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('Message id is required.');

        new MessageId(null);
    }
}
