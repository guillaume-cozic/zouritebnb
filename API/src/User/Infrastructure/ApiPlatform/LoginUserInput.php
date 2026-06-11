<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginUserInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Adresse email', example: 'host@example.com')]
        #[Assert\NotBlank]
        public string $email = '',
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Mot de passe', example: 'supersecret')]
        #[Assert\NotBlank]
        public string $password = '',
    ) {
    }
}
