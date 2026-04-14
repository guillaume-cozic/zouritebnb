<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class LoginUserInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Adresse email', example: 'host@example.com')]
        public string $email = '',
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Mot de passe', example: 'supersecret')]
        public string $password = '',
    ) {
    }
}
