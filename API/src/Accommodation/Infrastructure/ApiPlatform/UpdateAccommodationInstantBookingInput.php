<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationInstantBookingInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Active (true) ou désactive (false) la réservation instantanée : quand elle est active, les demandes des voyageurs sont confirmées automatiquement et le paiement capturé, sans validation de l\'hôte.', example: true)]
        #[Assert\NotNull]
        #[Assert\Type('bool')]
        public ?bool $instantBooking = null,
    ) {
    }
}
