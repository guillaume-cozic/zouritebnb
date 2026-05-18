<?php

declare(strict_types=1);

namespace App\Payment\Domain\Command;

final readonly class CreatePaymentIntentCommand
{
    /**
     * @param array<string, string|int|float|bool|null> $metadata
     */
    public function __construct(
        public int $amountCents,
        public string $currency,
        public string $description,
        public array $metadata = [],
    ) {
    }
}
