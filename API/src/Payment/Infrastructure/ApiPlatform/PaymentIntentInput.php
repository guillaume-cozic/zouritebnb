<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The client only describes WHAT it wants to book (accommodation + dates). The
 * amount, currency and Stripe metadata are derived server-side from the
 * accommodation's pricing, so a client can never set the amount it is charged.
 */
final readonly class PaymentIntentInput
{
    public function __construct(
        #[Groups(['payment_intent:write'])]
        #[ApiProperty(description: 'Identifiant UUID du logement à réserver.', example: '019cf27a-96ba-7957-8622-eeccb7350e79')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $accommodationId = '',

        #[Groups(['payment_intent:write'])]
        #[ApiProperty(description: "Date et heure d'arrivée (ISO 8601).", example: '2026-06-10T15:00:00')]
        #[Assert\NotBlank]
        public string $checkIn = '',

        #[Groups(['payment_intent:write'])]
        #[ApiProperty(description: 'Date et heure de départ (ISO 8601).', example: '2026-06-15T11:00:00')]
        #[Assert\NotBlank]
        public string $checkOut = '',
    ) {
    }
}
