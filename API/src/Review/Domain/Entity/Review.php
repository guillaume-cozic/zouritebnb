<?php

declare(strict_types=1);

namespace App\Review\Domain\Entity;

use App\Review\Domain\Event\ReviewSubmitted;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Review extends AggregateRoot
{
    private function __construct(
        private readonly Uuid $id,
        private readonly ReviewType $type,
        private readonly Uuid $reservationId,
        private readonly Uuid $authorUserId,
        private readonly ?Uuid $subjectAccommodationId,
        private readonly ?Uuid $subjectUserId,
        private readonly Rating $rating,
        private readonly ReviewComment $comment,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * A guest (authorUserId) reviews the accommodation (subjectAccommodationId).
     */
    public static function onAccommodation(
        Uuid $id,
        Uuid $reservationId,
        Uuid $authorUserId,
        Uuid $subjectAccommodationId,
        Rating $rating,
        ReviewComment $comment,
        \DateTimeImmutable $createdAt,
    ): self {
        $review = new self(
            id: $id,
            type: ReviewType::Accommodation,
            reservationId: $reservationId,
            authorUserId: $authorUserId,
            subjectAccommodationId: $subjectAccommodationId,
            subjectUserId: null,
            rating: $rating,
            comment: $comment,
            createdAt: $createdAt,
        );
        $review->recordEvent(new ReviewSubmitted(
            reviewId: $id,
            type: ReviewType::Accommodation,
            reservationId: $reservationId,
            authorUserId: $authorUserId,
            rating: $rating->toInt(),
        ));

        return $review;
    }

    /**
     * A host team member (authorUserId) reviews the guest (subjectUserId).
     */
    public static function onGuest(
        Uuid $id,
        Uuid $reservationId,
        Uuid $authorUserId,
        Uuid $subjectUserId,
        Rating $rating,
        ReviewComment $comment,
        \DateTimeImmutable $createdAt,
    ): self {
        $review = new self(
            id: $id,
            type: ReviewType::Guest,
            reservationId: $reservationId,
            authorUserId: $authorUserId,
            subjectAccommodationId: null,
            subjectUserId: $subjectUserId,
            rating: $rating,
            comment: $comment,
            createdAt: $createdAt,
        );
        $review->recordEvent(new ReviewSubmitted(
            reviewId: $id,
            type: ReviewType::Guest,
            reservationId: $reservationId,
            authorUserId: $authorUserId,
            rating: $rating->toInt(),
        ));

        return $review;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): ReviewType
    {
        return $this->type;
    }

    public function getReservationId(): Uuid
    {
        return $this->reservationId;
    }

    public function getAuthorUserId(): Uuid
    {
        return $this->authorUserId;
    }

    public function getSubjectAccommodationId(): ?Uuid
    {
        return $this->subjectAccommodationId;
    }

    public function getSubjectUserId(): ?Uuid
    {
        return $this->subjectUserId;
    }

    public function getRating(): Rating
    {
        return $this->rating;
    }

    public function getComment(): ReviewComment
    {
        return $this->comment;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
