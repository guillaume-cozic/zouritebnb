<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class PaymentCancelled implements DomainEvent
{
    public function __construct(
        public Uuid $paymentId,
        public string $stripePaymentIntentId,
    ) {
    }
}
