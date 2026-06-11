<?php

declare(strict_types=1);

namespace App\Payment\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class CreatePaymentIntentCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
        public Uuid $userId,
    ) {
    }
}
