<?php

declare(strict_types=1);

namespace App\Donation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Donation\Application\UseCase\CreateDonationIntent;
use App\Donation\Domain\Command\CreateDonationIntentCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<CreateDonationIntentInput, DonationIntentOutput>
 */
final readonly class CreateDonationIntentProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateDonationIntent $createDonationIntent,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DonationIntentOutput
    {
        if (!$data instanceof CreateDonationIntentInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', CreateDonationIntentInput::class, get_debug_type($data)));
        }

        $result = $this->handler->execute(fn () => $this->createDonationIntent->handle(new CreateDonationIntentCommand(
            solidarityProjectId: Uuid::fromString($data->solidarityProjectId),
            amountCents: $data->amountCents,
        )));

        $output = new DonationIntentOutput();
        $output->paymentIntentId = $result->paymentIntentId;
        $output->clientSecret = $result->clientSecret;

        return $output;
    }
}
