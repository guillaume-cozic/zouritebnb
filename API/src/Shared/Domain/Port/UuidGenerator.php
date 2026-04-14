<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

final class UuidGenerator
{
    private static ?Uuid $frozenUuid = null;

    public static function generate(): Uuid
    {
        return self::$frozenUuid ?? Uuid::v7();
    }

    public static function freeze(Uuid $uuid): void
    {
        self::$frozenUuid = $uuid;
    }

    public static function reset(): void
    {
        self::$frozenUuid = null;
    }
}
