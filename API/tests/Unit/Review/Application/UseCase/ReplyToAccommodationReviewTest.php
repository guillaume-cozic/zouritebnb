<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Application\UseCase;

use App\Review\Application\UseCase\ReplyToAccommodationReview;
use App\Review\Domain\Command\ReplyToAccommodationReviewCommand;
use App\Review\Domain\Entity\Rating;
use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewComment;
use App\Review\Domain\Exception\ReviewNotFoundException;
use App\Tests\Unit\Review\Infrastructure\FixedClock;
use App\Tests\Unit\Review\Infrastructure\InMemoryReviewRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReplyToAccommodationReviewTest extends TestCase
{
    private InMemoryReviewRepository $repository;
    private ReplyToAccommodationReview $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReviewRepository();
        $this->useCase = new ReplyToAccommodationReview(
            $this->repository,
            new FixedClock(new \DateTimeImmutable('2026-05-13T09:00:00+00:00')),
        );
    }

    public function test_should_store_the_reply_on_the_review(): void
    {
        $id = Uuid::v7();
        $this->repository->save(Review::onAccommodation(
            id: $id,
            reservationId: Uuid::v7(),
            authorUserId: Uuid::v7(),
            subjectAccommodationId: Uuid::v7(),
            rating: new Rating(5),
            comment: new ReviewComment('Un séjour vraiment agréable, logement propre, bien situé et hôte réactif.'),
            createdAt: new \DateTimeImmutable('2026-05-12T14:30:00+00:00'),
        ));

        $this->useCase->handle(new ReplyToAccommodationReviewCommand($id->toRfc4122(), 'Merci pour votre séjour !'));

        $review = $this->repository->findById($id);
        self::assertNotNull($review);
        self::assertSame('Merci pour votre séjour !', $review->getHostReply());
        self::assertEquals(new \DateTimeImmutable('2026-05-13T09:00:00+00:00'), $review->getHostReplyAt());
    }

    public function test_should_throw_when_review_not_found(): void
    {
        $this->expectException(ReviewNotFoundException::class);

        $this->useCase->handle(new ReplyToAccommodationReviewCommand(Uuid::v7()->toRfc4122(), 'Merci !'));
    }
}
