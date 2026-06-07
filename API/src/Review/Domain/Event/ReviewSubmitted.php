<?php

declare(strict_types=1);

namespace App\Review\Domain\Event;

use App\Review\Domain\Entity\ReviewType;
use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class ReviewSubmitted implements DomainEvent
{
    public function __construct(
        public Uuid $reviewId,
        public ReviewType $type,
        public Uuid $reservationId,
        public Uuid $authorUserId,
        public int $rating,
    ) {
    }
}
