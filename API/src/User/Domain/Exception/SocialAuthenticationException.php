<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\User\Domain\Entity\SocialProvider;

final class SocialAuthenticationException extends \DomainException
{
    public static function becauseProviderIsUnknown(string $provider): self
    {
        return new self(\sprintf('Unknown social provider "%s".', $provider));
    }

    public static function becauseProviderIsNotConfigured(SocialProvider $provider): self
    {
        return new self(\sprintf('Social login with %s is not enabled on this platform.', $provider->value));
    }

    public static function becauseTokenIsInvalid(SocialProvider $provider): self
    {
        return new self(\sprintf('The %s token could not be verified.', $provider->value));
    }

    public static function becauseEmailIsMissing(SocialProvider $provider): self
    {
        return new self(\sprintf('The %s account did not share a usable email address.', $provider->value));
    }
}
