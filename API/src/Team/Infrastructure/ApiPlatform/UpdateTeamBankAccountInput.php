<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateTeamBankAccountInput
{
    public function __construct(
        #[Groups(['team:write-bank-account'])]
        #[ApiProperty(description: 'IBAN du compte (ou null pour retirer le compte)', example: 'FR76 3000 1007 9412 3456 7890 185')]
        public ?string $iban = null,
        #[Groups(['team:write-bank-account'])]
        #[ApiProperty(description: 'BIC/SWIFT du compte (optionnel)', example: 'BDFEFRPPCCT')]
        public ?string $bic = null,
        #[Groups(['team:write-bank-account'])]
        #[ApiProperty(description: 'Nom du titulaire du compte (obligatoire si iban est fourni)', example: 'Marie Hôte')]
        public ?string $holderName = null,
    ) {
    }
}
