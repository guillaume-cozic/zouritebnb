<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class RegisterUserInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Adresse email', example: 'host@example.com')]
        public string $email = '',
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Mot de passe (8 caractères minimum)', example: 'supersecret')]
        public string $password = '',
    ) {
    }
}
