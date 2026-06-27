<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Domain\Entity;

use App\Review\Domain\Entity\Rating;
use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewComment;
use App\Review\Domain\Exception\InvalidHostReplyException;
use App\Review\Domain\Exception\ReviewNotAllowedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReviewReplyTest extends TestCase
{
    private const string COMMENT = 'Un séjour vraiment agréable, logement propre et bien situé, hôte réactif.';

    public function test_should_store_the_host_reply(): void
    {
        $review = $this->accommodationReview();

        $review->replyFromHost('  Merci pour votre séjour !  ', new \DateTimeImmutable('2026-05-13T09:00:00+00:00'));

        self::assertSame('Merci pour votre séjour !', $review->getHostReply());
        self::assertEquals(new \DateTimeImmutable('2026-05-13T09:00:00+00:00'), $review->getHostReplyAt());
    }

    public function test_should_replace_an_existing_reply(): void
    {
        $review = $this->accommodationReview();
        $review->replyFromHost('Première réponse', new \DateTimeImmutable('2026-05-13T09:00:00+00:00'));

        $review->replyFromHost('Réponse corrigée', new \DateTimeImmutable('2026-05-14T09:00:00+00:00'));

        self::assertSame('Réponse corrigée', $review->getHostReply());
    }

    public function test_should_reject_an_empty_reply(): void
    {
        $review = $this->accommodationReview();

        $this->expectException(InvalidHostReplyException::class);

        $review->replyFromHost('   ', new \DateTimeImmutable('2026-05-13T09:00:00+00:00'));
    }

    public function test_should_reject_replying_to_a_guest_review(): void
    {
        $review = Review::onGuest(
            id: Uuid::v7(),
            reservationId: Uuid::v7(),
            authorUserId: Uuid::v7(),
            subjectUserId: Uuid::v7(),
            rating: new Rating(5),
            comment: new ReviewComment(self::COMMENT),
            createdAt: new \DateTimeImmutable('2026-05-12T14:30:00+00:00'),
        );

        $this->expectException(ReviewNotAllowedException::class);

        $review->replyFromHost('Merci !', new \DateTimeImmutable('2026-05-13T09:00:00+00:00'));
    }

    private function accommodationReview(): Review
    {
        return Review::onAccommodation(
            id: Uuid::v7(),
            reservationId: Uuid::v7(),
            authorUserId: Uuid::v7(),
            subjectAccommodationId: Uuid::v7(),
            rating: new Rating(5),
            comment: new ReviewComment(self::COMMENT),
            createdAt: new \DateTimeImmutable('2026-05-12T14:30:00+00:00'),
        );
    }
}
