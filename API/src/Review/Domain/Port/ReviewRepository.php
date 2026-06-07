<?php

declare(strict_types=1);

namespace App\Review\Domain\Port;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewType;
use Symfony\Component\Uid\Uuid;

interface ReviewRepository
{
    public function save(Review $review): void;

    public function findById(Uuid $id): ?Review;

    /**
     * Checks whether a review already exists for a given author, stay (reservation) and direction.
     */
    public function existsForAuthorAndStay(Uuid $authorUserId, Uuid $reservationId, ReviewType $type): bool;
}
