<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Adresse email', example: 'host@example.com')]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Mot de passe (8 caractères minimum)', example: 'supersecret')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password = '',
    ) {
    }
}
