<?php

declare(strict_types=1);

namespace App\Donation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The donor freely chooses the amount (it is a donation, not a price), but the
 * currency and Stripe metadata are derived server-side. Precise bounds
 * (1 € to 10 000 €) are enforced by the domain.
 */
final readonly class CreateDonationIntentInput
{
    public function __construct(
        #[Groups(['donation_intent:write'])]
        #[ApiProperty(description: 'Identifiant UUID du projet solidaire bénéficiaire du don.', example: '019cf27a-96ba-7957-8622-eeccb7350e79')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $solidarityProjectId = '',

        #[Groups(['donation_intent:write'])]
        #[ApiProperty(description: "Montant du don en centimes d'euro, librement choisi par le donateur (entre 100, soit 1 €, et 1 000 000, soit 10 000 €).", example: 500)]
        #[Assert\Positive]
        public int $amountCents = 0,
    ) {
    }
}
