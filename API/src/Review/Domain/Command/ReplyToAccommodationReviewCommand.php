<?php

declare(strict_types=1);

namespace App\Review\Domain\Command;

final readonly class ReplyToAccommodationReviewCommand
{
    public function __construct(
        public string $reviewId,
        public string $reply,
    ) {
    }
}
