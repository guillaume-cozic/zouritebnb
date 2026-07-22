<?php

declare(strict_types=1);

namespace App\Donation\Domain\Entity;

/**
 * Result of {@see \App\Donation\Application\UseCase\CreateDonationIntent::handle()}.
 */
final readonly class CreatedDonationIntent
{
    public function __construct(
        public string $paymentIntentId,
        public string $clientSecret,
    ) {
    }
}
