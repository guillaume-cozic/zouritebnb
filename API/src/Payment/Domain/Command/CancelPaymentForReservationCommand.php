<?php

declare(strict_types=1);

namespace App\Payment\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class CancelPaymentForReservationCommand
{
    public function __construct(public Uuid $reservationId)
    {
    }
}
