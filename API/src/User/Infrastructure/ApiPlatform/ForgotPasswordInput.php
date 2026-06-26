<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ForgotPasswordInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Adresse email du compte dont le mot de passe doit être réinitialisé', example: 'host@example.com')]
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',
    ) {
    }
}
