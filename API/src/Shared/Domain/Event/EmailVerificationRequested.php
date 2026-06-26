<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Published by the User context when an email verification link is requested (on
 * registration, or on an explicit resend). The Notification context consumes it to
 * email the verification link. Carries the raw (unhashed) token so the listener can
 * build the link — only its hash is persisted in the database.
 */
final readonly class EmailVerificationRequested implements DomainEvent
{
    public function __construct(public Uuid $userId, public string $token)
    {
    }
}
