<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\IpRateLimiter;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\User\Application\UseCase\RequestPasswordReset;
use App\User\Domain\Command\RequestPasswordResetCommand;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * @implements ProcessorInterface<ForgotPasswordInput, void>
 */
final readonly class ForgotPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestPasswordReset $requestPasswordReset,
        private TransactionalUseCaseHandler $handler,
        private IpRateLimiter $rateLimiter,
        private RateLimiterFactoryInterface $passwordResetLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof ForgotPasswordInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', ForgotPasswordInput::class, get_debug_type($data)));
        }

        // Throttle abuse (mass enumeration / mail-bombing) before touching the DB.
        $this->rateLimiter->enforce($this->passwordResetLimiter);

        $this->handler->execute(fn () => $this->requestPasswordReset->handle(
            new RequestPasswordResetCommand(email: $data->email),
        ));
    }
}
