<?php

declare(strict_types=1);

namespace App\Review\Application\UseCase;

use App\Review\Domain\Command\SubmitGuestReviewCommand;
use App\Review\Domain\Entity\Rating;
use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewComment;
use App\Review\Domain\Entity\ReviewType;
use App\Review\Domain\Exception\ReviewNotAllowedException;
use App\Review\Domain\Port\CompletedStayChecker;
use App\Review\Domain\Port\ReviewRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class SubmitGuestReview
{
    public function __construct(
        private ReviewRepository $repository,
        private CompletedStayChecker $completedStayChecker,
        private EventBus $eventBus,
        private Clock $clock,
    ) {
    }

    public function handle(SubmitGuestReviewCommand $command): void
    {
        $stay = $this->completedStayChecker->findCompletedStay($command->guestUserId, $command->accommodationId);
        if (null === $stay) {
            throw ReviewNotAllowedException::becauseStayNotCompleted();
        }

        if ($this->repository->existsForAuthorAndStay($command->authorUserId, $stay->reservationId, ReviewType::Guest)) {
            throw ReviewNotAllowedException::becauseReviewAlreadySubmitted();
        }

        $review = Review::onGuest(
            id: UuidGenerator::generate(),
            reservationId: $stay->reservationId,
            authorUserId: $command->authorUserId,
            subjectUserId: $command->guestUserId,
            rating: new Rating($command->rating),
            comment: new ReviewComment($command->comment),
            createdAt: $this->clock->now(),
        );

        $this->repository->save($review);
        $this->eventBus->dispatch($review->releaseEvents());
    }
}
