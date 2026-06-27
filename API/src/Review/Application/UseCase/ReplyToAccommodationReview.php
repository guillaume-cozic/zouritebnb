<?php

declare(strict_types=1);

namespace App\Review\Application\UseCase;

use App\Review\Domain\Command\ReplyToAccommodationReviewCommand;
use App\Review\Domain\Exception\ReviewNotFoundException;
use App\Review\Domain\Port\ReviewRepository;
use App\Shared\Domain\Port\Clock;
use Symfony\Component\Uid\Uuid;

final readonly class ReplyToAccommodationReview
{
    public function __construct(
        private ReviewRepository $repository,
        private Clock $clock,
    ) {
    }

    public function handle(ReplyToAccommodationReviewCommand $command): void
    {
        $review = $this->repository->findById(Uuid::fromString($command->reviewId));

        if (null === $review) {
            throw ReviewNotFoundException::becauseId($command->reviewId);
        }

        $review->replyFromHost($command->reply, $this->clock->now());
        $this->repository->save($review);
    }
}
