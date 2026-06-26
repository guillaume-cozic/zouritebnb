<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\IpRateLimiter;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\VerifyEmail;
use App\User\Domain\Command\VerifyEmailCommand;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * @implements ProcessorInterface<VerifyEmailInput, void>
 */
final readonly class VerifyEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private VerifyEmail $verifyEmail,
        private TransactionalUseCaseHandler $handler,
        private IpRateLimiter $rateLimiter,
        private RateLimiterFactoryInterface $verifyEmailLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof VerifyEmailInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', VerifyEmailInput::class, get_debug_type($data)));
        }

        // Throttle brute-forcing of verification tokens.
        $this->rateLimiter->enforce($this->verifyEmailLimiter);

        $this->handler->execute(fn () => $this->verifyEmail->handle(
            new VerifyEmailCommand(token: $data->token),
        ));
    }
}
