<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTeamBankAccountInput
{
    public function __construct(
        #[Groups(['team:write-bank-account'])]
        #[ApiProperty(description: 'IBAN du compte (ou null pour retirer le compte)', example: 'FR76 3000 1007 9412 3456 7890 185')]
        #[Assert\Iban]
        public ?string $iban = null,
        #[Groups(['team:write-bank-account'])]
        #[ApiProperty(description: 'BIC/SWIFT du compte (optionnel)', example: 'BDFEFRPPCCT')]
        #[Assert\Regex(pattern: '/^\s*[a-z]{6}[a-z0-9]{2}([a-z0-9]{3})?\s*$/i', message: 'Format BIC invalide.')]
        public ?string $bic = null,
        #[Groups(['team:write-bank-account'])]
        #[ApiProperty(description: 'Nom du titulaire du compte (obligatoire si iban est fourni)', example: 'Marie Hôte')]
        #[Assert\When(
            expression: 'this.iban != null and this.iban != ""',
            constraints: [
                new Assert\NotBlank(normalizer: 'trim'),
                new Assert\Length(max: 70, normalizer: 'trim'),
            ],
        )]
        public ?string $holderName = null,
    ) {
    }
}
