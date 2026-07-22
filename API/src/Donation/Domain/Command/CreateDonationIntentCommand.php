<?php

declare(strict_types=1);

namespace App\Donation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class CreateDonationIntentCommand
{
    public function __construct(
        public Uuid $solidarityProjectId,
        public int $amountCents,
    ) {
    }
}
