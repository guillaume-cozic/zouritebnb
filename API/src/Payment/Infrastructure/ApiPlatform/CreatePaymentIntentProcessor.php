<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Payment\Application\UseCase\CreatePaymentIntent;
use App\Payment\Domain\Command\CreatePaymentIntentCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;

/**
 * @implements ProcessorInterface<PaymentIntentInput, PaymentIntentOutput>
 */
final readonly class CreatePaymentIntentProcessor implements ProcessorInterface
{
    public function __construct(
        private CreatePaymentIntent $createPaymentIntent,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PaymentIntentOutput
    {
        if (!$data instanceof PaymentIntentInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', PaymentIntentInput::class, get_debug_type($data)));
        }

        $result = $this->handler->execute(fn () => $this->createPaymentIntent->handle(new CreatePaymentIntentCommand(
            amountCents: $data->amountCents,
            currency: $data->currency,
            description: $data->description,
            metadata: $data->metadata,
        )));

        $output = new PaymentIntentOutput();
        $output->paymentIntentId = $result->paymentIntentId;
        $output->clientSecret = $result->clientSecret;

        return $output;
    }
}
