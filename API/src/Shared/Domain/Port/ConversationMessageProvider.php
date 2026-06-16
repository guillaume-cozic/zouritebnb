<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

interface ConversationMessageProvider
{
    public function findMessage(Uuid $conversationId, Uuid $messageId): ?ConversationMessageView;
}
