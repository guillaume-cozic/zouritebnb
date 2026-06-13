<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminSolidarityProjectInput
{
    /**
     * @param array<array{value: string, label: string}> $keyFigures
     */
    public function __construct(
        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'Titre du projet solidaire', example: 'Reforestation de l\'île Rodrigues')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title = '',

        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'Description détaillée du projet', example: 'Plantation de 10 000 arbres endémiques sur trois ans.')]
        #[Assert\NotBlank]
        public string $description = '',

        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'URL de l\'image du projet (optionnelle)', example: 'https://example.com/images/project.jpg')]
        #[Assert\Url]
        #[Assert\Length(max: 2048)]
        public ?string $imageUrl = null,

        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(description: 'Statut initial du projet (active ou closed)', example: 'active')]
        #[Assert\Choice(choices: [SolidarityProject::STATUS_ACTIVE, SolidarityProject::STATUS_CLOSED])]
        public string $status = SolidarityProject::STATUS_ACTIVE,

        /**
         * @var array<array{value: string, label: string}>
         */
        #[Groups(['admin_solidarity_project:write'])]
        #[ApiProperty(
            description: 'Chiffres clés du projet (valeur + libellé)',
            example: [['value' => '10 000', 'label' => 'arbres plantés']],
        )]
        #[Assert\All([
            new Assert\Collection(
                fields: [
                    'value' => [new Assert\NotBlank(), new Assert\Type('string')],
                    'label' => [new Assert\NotBlank(), new Assert\Type('string')],
                ],
                allowExtraFields: false,
            ),
        ])]
        public array $keyFigures = [],
    ) {
    }
}
