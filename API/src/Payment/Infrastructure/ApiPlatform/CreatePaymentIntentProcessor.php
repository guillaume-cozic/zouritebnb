<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Payment\Application\UseCase\CreatePaymentIntent;
use App\Payment\Domain\Command\CreatePaymentIntentCommand;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<PaymentIntentInput, PaymentIntentOutput>
 */
final readonly class CreatePaymentIntentProcessor implements ProcessorInterface
{
    public function __construct(
        private CreatePaymentIntent $createPaymentIntent,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PaymentIntentOutput
    {
        if (!$data instanceof PaymentIntentInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', PaymentIntentInput::class, get_debug_type($data)));
        }

        $result = $this->handler->execute(fn () => $this->createPaymentIntent->handle(new CreatePaymentIntentCommand(
            accommodationId: Uuid::fromString($data->accommodationId),
            checkIn: new \DateTimeImmutable($data->checkIn),
            checkOut: new \DateTimeImmutable($data->checkOut),
            userId: $this->currentUser->id(),
        )));

        $output = new PaymentIntentOutput();
        $output->paymentIntentId = $result->paymentIntentId;
        $output->clientSecret = $result->clientSecret;

        return $output;
    }
}
