<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Infrastructure;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewType;
use App\Review\Domain\Port\ReviewRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryReviewRepository implements ReviewRepository
{
    /** @var Review[] */
    private array $reviews = [];

    public function save(Review $review): void
    {
        $this->reviews[$review->getId()->toRfc4122()] = $review;
    }

    public function findById(Uuid $id): ?Review
    {
        return $this->reviews[$id->toRfc4122()] ?? null;
    }

    public function existsForAuthorAndStay(Uuid $authorUserId, Uuid $reservationId, ReviewType $type): bool
    {
        foreach ($this->reviews as $review) {
            if (!$review->getAuthorUserId()->equals($authorUserId)) {
                continue;
            }
            if (!$review->getReservationId()->equals($reservationId)) {
                continue;
            }
            if ($review->getType() !== $type) {
                continue;
            }

            return true;
        }

        return false;
    }

    /** @return Review[] */
    public function all(): array
    {
        return array_values($this->reviews);
    }
}
