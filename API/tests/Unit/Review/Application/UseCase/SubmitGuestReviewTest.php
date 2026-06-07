<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Application\UseCase;

use App\Review\Application\UseCase\SubmitGuestReview;
use App\Review\Domain\Command\SubmitGuestReviewCommand;
use App\Review\Domain\Entity\ReviewType;
use App\Review\Domain\Event\ReviewSubmitted;
use App\Review\Domain\Exception\InvalidRatingException;
use App\Review\Domain\Exception\InvalidReviewCommentException;
use App\Review\Domain\Exception\ReviewNotAllowedException;
use App\Review\Domain\Port\CompletedStay;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Review\Infrastructure\FixedClock;
use App\Tests\Unit\Review\Infrastructure\InMemoryCompletedStayChecker;
use App\Tests\Unit\Review\Infrastructure\InMemoryReviewRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SubmitGuestReviewTest extends TestCase
{
    private const string COMMENT = 'Great guest, left the place spotless and communicated very well.';

    private InMemoryReviewRepository $repository;
    private InMemoryCompletedStayChecker $stayChecker;
    private InMemoryEventBus $eventBus;
    private SubmitGuestReview $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReviewRepository();
        $this->stayChecker = new InMemoryCompletedStayChecker();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new SubmitGuestReview(
            $this->repository,
            $this->stayChecker,
            $this->eventBus,
            new FixedClock(new \DateTimeImmutable('2026-06-07 12:00:00')),
        );
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_store_review_and_dispatch_event(): void
    {
        $reviewId = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        $reservationId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000a1');
        $accommodationId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000b1');
        $guestUserId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000c1');
        $hostUserId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000d1');
        UuidGenerator::freeze($reviewId);
        $this->stayChecker->addCompletedStay(new CompletedStay($reservationId, $accommodationId, $guestUserId));

        $this->useCase->handle(new SubmitGuestReviewCommand(
            authorUserId: $hostUserId,
            accommodationId: $accommodationId,
            guestUserId: $guestUserId,
            rating: 5,
            comment: self::COMMENT,
        ));

        $review = $this->repository->findById($reviewId);
        self::assertNotNull($review);
        self::assertSame(ReviewType::Guest, $review->getType());
        self::assertTrue($reservationId->equals($review->getReservationId()));
        self::assertTrue($hostUserId->equals($review->getAuthorUserId()));
        self::assertNull($review->getSubjectAccommodationId());
        self::assertNotNull($review->getSubjectUserId());
        self::assertTrue($guestUserId->equals($review->getSubjectUserId()));
        self::assertSame(5, $review->getRating()->toInt());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReviewSubmitted::class, $events[0]);
        self::assertSame(ReviewType::Guest, $events[0]->type);
    }

    public function test_should_throw_when_no_completed_stay(): void
    {
        $this->expectException(ReviewNotAllowedException::class);
        $this->expectExceptionMessage('A review can only be submitted after a confirmed stay has ended.');

        $this->useCase->handle(new SubmitGuestReviewCommand(
            authorUserId: Uuid::v7(),
            accommodationId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            rating: 5,
            comment: self::COMMENT,
        ));
    }

    public function test_should_throw_when_review_already_submitted(): void
    {
        $reservationId = Uuid::v7();
        $accommodationId = Uuid::v7();
        $guestUserId = Uuid::v7();
        $hostUserId = Uuid::v7();
        $this->stayChecker->addCompletedStay(new CompletedStay($reservationId, $accommodationId, $guestUserId));

        $command = new SubmitGuestReviewCommand(
            authorUserId: $hostUserId,
            accommodationId: $accommodationId,
            guestUserId: $guestUserId,
            rating: 4,
            comment: self::COMMENT,
        );
        $this->useCase->handle($command);

        $this->expectException(ReviewNotAllowedException::class);
        $this->expectExceptionMessage('A review has already been submitted for this stay.');

        $this->useCase->handle($command);
    }

    public function test_should_reject_out_of_bounds_rating(): void
    {
        $accommodationId = Uuid::v7();
        $guestUserId = Uuid::v7();
        $this->stayChecker->addCompletedStay(new CompletedStay(Uuid::v7(), $accommodationId, $guestUserId));

        $this->expectException(InvalidRatingException::class);

        $this->useCase->handle(new SubmitGuestReviewCommand(
            authorUserId: Uuid::v7(),
            accommodationId: $accommodationId,
            guestUserId: $guestUserId,
            rating: 0,
            comment: self::COMMENT,
        ));
    }

    public function test_should_reject_too_short_comment(): void
    {
        $accommodationId = Uuid::v7();
        $guestUserId = Uuid::v7();
        $this->stayChecker->addCompletedStay(new CompletedStay(Uuid::v7(), $accommodationId, $guestUserId));

        $this->expectException(InvalidReviewCommentException::class);

        $this->useCase->handle(new SubmitGuestReviewCommand(
            authorUserId: Uuid::v7(),
            accommodationId: $accommodationId,
            guestUserId: $guestUserId,
            rating: 4,
            comment: 'Too short.',
        ));
    }
}
