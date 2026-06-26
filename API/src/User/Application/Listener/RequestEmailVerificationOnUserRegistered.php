<?php

declare(strict_types=1);

namespace App\User\Application\Listener;

use App\Shared\Domain\Event\UserRegistered;
use App\User\Application\UseCase\RequestEmailVerification;
use App\User\Domain\Command\RequestEmailVerificationCommand;

/**
 * Kicks off email verification right after registration by issuing a verification
 * token (which in turn triggers the verification email via the Notification context).
 * Kept separate from RegisterUser so the two concerns stay decoupled.
 */
final readonly class RequestEmailVerificationOnUserRegistered
{
    public function __construct(private RequestEmailVerification $requestEmailVerification)
    {
    }

    public function __invoke(UserRegistered $event): void
    {
        $this->requestEmailVerification->handle(new RequestEmailVerificationCommand($event->userId));
    }
}
