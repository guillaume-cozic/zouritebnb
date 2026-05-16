<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

final class UuidGenerator
{
    private static ?Uuid $frozenUuid = null;

    /** @var Uuid[] */
    private static array $queue = [];

    public static function generate(): Uuid
    {
        if ([] !== self::$queue) {
            return array_shift(self::$queue);
        }

        return self::$frozenUuid ?? Uuid::v7();
    }

    public static function freeze(Uuid $uuid): void
    {
        self::$frozenUuid = $uuid;
    }

    /**
     * @param Uuid[] $uuids
     */
    public static function queue(array $uuids): void
    {
        self::$queue = array_values($uuids);
    }

    public static function reset(): void
    {
        self::$frozenUuid = null;
        self::$queue = [];
    }
}
