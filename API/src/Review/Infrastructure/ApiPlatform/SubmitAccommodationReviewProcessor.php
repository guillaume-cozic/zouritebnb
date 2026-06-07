<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Review\Application\UseCase\SubmitAccommodationReview;
use App\Review\Domain\Command\SubmitAccommodationReviewCommand;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<SubmitAccommodationReviewInput, void>
 */
final readonly class SubmitAccommodationReviewProcessor implements ProcessorInterface
{
    public function __construct(
        private SubmitAccommodationReview $submitAccommodationReview,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof SubmitAccommodationReviewInput);

        $this->handler->execute(fn () => $this->submitAccommodationReview->handle(new SubmitAccommodationReviewCommand(
            authorUserId: $this->currentUser->id(),
            accommodationId: Uuid::fromString($data->accommodationId),
            rating: $data->rating,
            comment: $data->comment,
        )));
    }
}
