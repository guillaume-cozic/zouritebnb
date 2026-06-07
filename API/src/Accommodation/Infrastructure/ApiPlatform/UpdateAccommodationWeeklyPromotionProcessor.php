<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationWeeklyPromotion;
use App\Accommodation\Domain\Command\UpdateAccommodationWeeklyPromotionCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationWeeklyPromotionInput, void>
 */
final readonly class UpdateAccommodationWeeklyPromotionProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationWeeklyPromotion $updateAccommodationWeeklyPromotion,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationWeeklyPromotion->handle(new UpdateAccommodationWeeklyPromotionCommand(
            accommodationId: $id,
            weeklyPromotionPercentage: $data->weeklyPromotionPercentage,
        )));
    }
}
