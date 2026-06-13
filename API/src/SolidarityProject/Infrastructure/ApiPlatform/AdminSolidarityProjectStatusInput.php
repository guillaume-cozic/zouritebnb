<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdminSolidarityProjectStatusInput
{
    public function __construct(
        #[Groups(['admin_solidarity_project:status'])]
        #[ApiProperty(description: 'Nouveau statut : "active" pour publier, "closed" pour désactiver', example: 'closed')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [SolidarityProject::STATUS_ACTIVE, SolidarityProject::STATUS_CLOSED])]
        public string $status = SolidarityProject::STATUS_ACTIVE,
    ) {
    }
}
