<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Infrastructure\Doctrine;

use App\Conversation\Infrastructure\Doctrine\ConversationEntity;
use App\Conversation\Infrastructure\Doctrine\MessageEntity;
use PHPUnit\Framework\TestCase;

final class MessageEntityTest extends TestCase
{
    public function test_should_expose_its_conversation(): void
    {
        $conversation = new ConversationEntity();
        $message = (new MessageEntity())->setConversation($conversation);

        self::assertSame($conversation, $message->getConversation());
    }

    public function test_should_allow_detaching_its_conversation(): void
    {
        $message = (new MessageEntity())->setConversation(new ConversationEntity());

        $message->setConversation(null);

        self::assertNull($message->getConversation());
    }
}
