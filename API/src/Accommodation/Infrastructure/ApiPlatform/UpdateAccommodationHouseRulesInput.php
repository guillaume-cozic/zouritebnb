<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationHouseRulesInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Fumeurs autorisés dans le logement.', example: false)]
        public bool $smokingAllowed = false,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Animaux de compagnie acceptés.', example: true)]
        public bool $petsAllowed = false,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Fêtes et événements autorisés.', example: false)]
        public bool $partiesAllowed = false,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Règles complémentaires en texte libre (max 1000 caractères). Null ou vide pour retirer.', example: 'Merci de retirer vos chaussures à l\'intérieur.')]
        #[Assert\Length(max: 1000)]
        public ?string $houseRulesNotes = null,
    ) {
    }
}
