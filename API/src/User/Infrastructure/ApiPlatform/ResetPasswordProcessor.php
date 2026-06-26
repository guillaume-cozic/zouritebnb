<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\IpRateLimiter;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\ResetPassword;
use App\User\Domain\Command\ResetPasswordCommand;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * @implements ProcessorInterface<ResetPasswordInput, void>
 */
final readonly class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private ResetPassword $resetPassword,
        private TransactionalUseCaseHandler $handler,
        private IpRateLimiter $rateLimiter,
        private RateLimiterFactoryInterface $passwordResetLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof ResetPasswordInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', ResetPasswordInput::class, get_debug_type($data)));
        }

        // Throttle brute-forcing of reset tokens.
        $this->rateLimiter->enforce($this->passwordResetLimiter);

        $this->handler->execute(fn () => $this->resetPassword->handle(
            new ResetPasswordCommand(token: $data->token, newPassword: $data->password),
        ));
    }
}
