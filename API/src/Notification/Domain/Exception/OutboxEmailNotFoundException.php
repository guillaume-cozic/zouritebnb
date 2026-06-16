<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use Symfony\Component\Uid\Uuid;

final class OutboxEmailNotFoundException extends \RuntimeException
{
    public static function withId(Uuid $id): self
    {
        return new self(\sprintf('No outbox email found with id "%s".', $id->toRfc4122()));
    }
}
