<?php

declare(strict_types=1);

namespace App\Review\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class SubmitGuestReviewCommand
{
    public function __construct(
        public Uuid $authorUserId,
        public Uuid $accommodationId,
        public Uuid $guestUserId,
        public ?int $rating,
        public ?string $comment,
    ) {
    }
}
