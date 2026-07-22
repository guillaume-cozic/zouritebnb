<?php

declare(strict_types=1);

namespace App\Donation\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class DonationCancelled implements DomainEvent
{
    public function __construct(
        public Uuid $donationId,
        public string $stripePaymentIntentId,
    ) {
    }
}
