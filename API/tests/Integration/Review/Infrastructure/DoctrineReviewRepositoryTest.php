<?php

declare(strict_types=1);

namespace App\Tests\Integration\Review\Infrastructure;

use App\Review\Domain\Entity\Rating;
use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewComment;
use App\Review\Domain\Entity\ReviewType;
use App\Review\Domain\Port\ReviewRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineReviewRepositoryTest extends RepositoryTestCase
{
    private const string COMMENT = 'This is a perfectly lovely stay and the host was extremely welcoming!';

    private ReviewRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(ReviewRepository::class);
    }

    public function test_should_save_and_find_accommodation_review_by_id(): void
    {
        $id = Uuid::v4();
        $reservationId = Uuid::v4();
        $authorUserId = Uuid::v4();
        $accommodationId = Uuid::v4();
        $createdAt = new \DateTimeImmutable('2026-05-10 12:00:00');

        $review = Review::onAccommodation(
            id: $id,
            reservationId: $reservationId,
            authorUserId: $authorUserId,
            subjectAccommodationId: $accommodationId,
            rating: new Rating(4),
            comment: new ReviewComment(self::COMMENT),
            createdAt: $createdAt,
        );

        $this->repository->save($review);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame(ReviewType::Accommodation, $found->getType());
        self::assertEquals($reservationId, $found->getReservationId());
        self::assertEquals($authorUserId, $found->getAuthorUserId());
        self::assertEquals($accommodationId, $found->getSubjectAccommodationId());
        self::assertNull($found->getSubjectUserId());
        self::assertSame(4, $found->getRating()->toInt());
        self::assertSame(self::COMMENT, $found->getComment()->toString());
        self::assertEquals($createdAt, $found->getCreatedAt());
    }

    public function test_should_save_and_find_guest_review_by_id(): void
    {
        $id = Uuid::v4();
        $subjectUserId = Uuid::v4();

        $review = Review::onGuest(
            id: $id,
            reservationId: Uuid::v4(),
            authorUserId: Uuid::v4(),
            subjectUserId: $subjectUserId,
            rating: new Rating(5),
            comment: new ReviewComment(self::COMMENT),
            createdAt: new \DateTimeImmutable('2026-05-11 09:30:00'),
        );

        $this->repository->save($review);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame(ReviewType::Guest, $found->getType());
        self::assertEquals($subjectUserId, $found->getSubjectUserId());
        self::assertNull($found->getSubjectAccommodationId());
        self::assertSame(5, $found->getRating()->toInt());
    }

    public function test_should_return_null_when_not_found(): void
    {
        self::assertNull($this->repository->findById(Uuid::v4()));
    }

    public function test_exists_returns_false_when_no_review(): void
    {
        $exists = $this->repository->existsForAuthorAndStay(
            Uuid::v4(),
            Uuid::v4(),
            ReviewType::Accommodation,
        );

        self::assertFalse($exists);
    }

    public function test_exists_returns_true_after_saving_matching_review(): void
    {
        $authorUserId = Uuid::v4();
        $reservationId = Uuid::v4();

        $review = Review::onAccommodation(
            id: Uuid::v4(),
            reservationId: $reservationId,
            authorUserId: $authorUserId,
            subjectAccommodationId: Uuid::v4(),
            rating: new Rating(3),
            comment: new ReviewComment(self::COMMENT),
            createdAt: new \DateTimeImmutable('2026-05-12 14:00:00'),
        );
        $this->repository->save($review);

        self::assertTrue($this->repository->existsForAuthorAndStay($authorUserId, $reservationId, ReviewType::Accommodation));
        // Same author + reservation but other direction has no review yet.
        self::assertFalse($this->repository->existsForAuthorAndStay($authorUserId, $reservationId, ReviewType::Guest));
        // Different author has no review.
        self::assertFalse($this->repository->existsForAuthorAndStay(Uuid::v4(), $reservationId, ReviewType::Accommodation));
    }
}
