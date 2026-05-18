<?php

declare(strict_types=1);

namespace App\Payment\Domain\Entity;

/**
 * Result of {@see \App\Payment\Application\UseCase\CreatePaymentIntent::handle()}.
 */
final readonly class CreatedPaymentIntent
{
    public function __construct(
        public string $paymentIntentId,
        public string $clientSecret,
    ) {
    }
}
