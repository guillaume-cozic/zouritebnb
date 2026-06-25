<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationCancellationPolicyInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Politique d\'annulation choisie par l\'hôte. "flexible" : remboursement intégral jusqu\'à 24h avant l\'arrivée. "moderate" : remboursement intégral jusqu\'à 5 jours avant, puis 50%.', example: 'moderate')]
        #[Assert\NotNull]
        #[Assert\Choice(choices: ['flexible', 'moderate'])]
        public ?string $cancellationPolicy = null,
    ) {
    }
}
