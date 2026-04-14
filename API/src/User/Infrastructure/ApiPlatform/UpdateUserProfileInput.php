<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateUserProfileInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Prénom', example: 'Marie')]
        public ?string $firstName = null,
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Nom', example: 'Dupont')]
        public ?string $lastName = null,
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Adresse email', example: 'marie@example.com')]
        public string $email = '',
    ) {
    }
}
