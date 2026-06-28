<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationDynamicPricing;
use App\Accommodation\Domain\Command\UpdateAccommodationDynamicPricingCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationDynamicPricingInput, void>
 */
final readonly class UpdateAccommodationDynamicPricingProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationDynamicPricing $updateAccommodationDynamicPricing,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateAccommodationDynamicPricingInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateAccommodationDynamicPricingInput::class, get_debug_type($data)));
        }

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationDynamicPricing->handle(new UpdateAccommodationDynamicPricingCommand(
            accommodationId: $id,
            weekendSurchargePercentage: $data->weekendSurchargePercentage,
            lastMinuteDiscountPercentage: $data->lastMinuteDiscountPercentage,
            lastMinuteDays: $data->lastMinuteDays,
        )));
    }
}
