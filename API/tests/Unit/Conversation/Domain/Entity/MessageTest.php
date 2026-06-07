<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Domain\Entity;

use App\Conversation\Domain\Entity\Message;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class MessageTest extends TestCase
{
    public function test_should_create_a_user_message(): void
    {
        $id = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000010'));
        $body = new MessageBody('Hello host');
        $authorUserId = Uuid::fromString('01961e2f-dead-7000-beef-000000000020');
        $sentAt = new \DateTimeImmutable('2026-04-13T10:00:00+00:00');

        $message = Message::user($id, $body, $authorUserId, $sentAt);

        self::assertSame($id, $message->getId());
        self::assertSame($body, $message->getBody());
        self::assertSame($authorUserId, $message->getAuthorUserId());
        self::assertSame($sentAt, $message->getSentAt());
        self::assertFalse($message->isSystem());
    }

    public function test_should_create_a_system_message(): void
    {
        $id = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000011'));
        $body = new MessageBody('Reservation confirmed');
        $sentAt = new \DateTimeImmutable('2026-04-13T11:00:00+00:00');

        $message = Message::system($id, $body, $sentAt);

        self::assertSame($id, $message->getId());
        self::assertSame($body, $message->getBody());
        self::assertNull($message->getAuthorUserId());
        self::assertSame($sentAt, $message->getSentAt());
        self::assertTrue($message->isSystem());
    }

    public function test_should_create_a_message_via_constructor(): void
    {
        $id = new MessageId(Uuid::fromString('01961e2f-dead-7000-beef-000000000012'));
        $body = new MessageBody('Direct construction');
        $authorUserId = Uuid::fromString('01961e2f-dead-7000-beef-000000000021');
        $sentAt = new \DateTimeImmutable('2026-04-13T12:00:00+00:00');

        $message = new Message($id, $body, $authorUserId, $sentAt, false);

        self::assertSame($id, $message->getId());
        self::assertSame($body, $message->getBody());
        self::assertSame($authorUserId, $message->getAuthorUserId());
        self::assertSame($sentAt, $message->getSentAt());
        self::assertFalse($message->isSystem());
    }
}
