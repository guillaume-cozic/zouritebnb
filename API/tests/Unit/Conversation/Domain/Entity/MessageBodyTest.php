<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Domain\Entity;

use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Exception\InvalidMessageException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MessageBodyTest extends TestCase
{
    public function test_should_expose_the_value(): void
    {
        $body = new MessageBody('Hello, is the room still available?');

        self::assertSame('Hello, is the room still available?', $body->toString());
    }

    public function test_should_accept_value_at_max_length(): void
    {
        $value = str_repeat('a', 5000);

        $body = new MessageBody($value);

        self::assertSame($value, $body->toString());
    }

    public function test_should_throw_when_value_is_null(): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('Message body is required.');

        new MessageBody(null);
    }

    #[DataProvider('blankValues')]
    public function test_should_throw_when_value_is_blank(string $value): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('Message body must not be empty.');

        new MessageBody($value);
    }

    public static function blankValues(): \Generator
    {
        yield 'empty string' => [''];
        yield 'spaces only' => ['   '];
        yield 'tabs and newlines' => ["\t\n  \n"];
    }

    public function test_should_throw_when_value_exceeds_max_length(): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('Message body must not exceed 5000 characters.');

        new MessageBody(str_repeat('a', 5001));
    }
}
