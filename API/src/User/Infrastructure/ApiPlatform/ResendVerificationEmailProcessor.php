<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\Security\IpRateLimiter;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\RequestEmailVerification;
use App\User\Domain\Command\RequestEmailVerificationCommand;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Re-issues a verification email for the currently authenticated user (e.g. when the
 * original link expired or was lost). No request body: the user comes from the JWT.
 *
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class ResendVerificationEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestEmailVerification $requestEmailVerification,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
        private IpRateLimiter $rateLimiter,
        private RateLimiterFactoryInterface $verifyEmailLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->rateLimiter->enforce($this->verifyEmailLimiter);

        $userId = $this->currentUser->id();

        $this->handler->execute(fn () => $this->requestEmailVerification->handle(
            new RequestEmailVerificationCommand(userId: $userId),
        ));
    }
}
