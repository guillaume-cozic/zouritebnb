<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Applies a per-client-IP rate limit using a configured limiter factory, and
 * throws HTTP 429 when the limit is exceeded. Used to throttle sensitive
 * unauthenticated endpoints (login, register) that are reached through API
 * Platform processors rather than Symfony's login authenticator.
 */
final readonly class IpRateLimiter
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function enforce(RateLimiterFactoryInterface $factory): void
    {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';

        if (!$factory->create($ip)->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many requests. Please slow down and retry later.');
        }
    }
}
