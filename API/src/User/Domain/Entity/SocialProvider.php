<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Exception\SocialAuthenticationException;

enum SocialProvider: string
{
    case Google = 'google';
    case Apple = 'apple';
    case Facebook = 'facebook';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw SocialAuthenticationException::becauseProviderIsUnknown($value);
    }
}
