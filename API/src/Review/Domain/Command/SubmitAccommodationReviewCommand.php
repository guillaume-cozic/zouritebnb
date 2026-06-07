<?php

declare(strict_types=1);

namespace App\Review\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class SubmitAccommodationReviewCommand
{
    public function __construct(
        public Uuid $authorUserId,
        public Uuid $accommodationId,
        public ?int $rating,
        public ?string $comment,
    ) {
    }
}
