<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserProfileInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Prénom', example: 'Marie')]
        #[Assert\Length(max: 100)]
        public ?string $firstName = null,
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Nom', example: 'Dupont')]
        #[Assert\Length(max: 100)]
        public ?string $lastName = null,
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Adresse email', example: 'marie@example.com')]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',
    ) {
    }
}
