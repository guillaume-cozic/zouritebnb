<?php

declare(strict_types=1);

namespace App\Payment\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class LinkPaymentToReservationCommand
{
    public function __construct(
        public string $paymentIntentId,
        public Uuid $reservationId,
    ) {
    }
}
