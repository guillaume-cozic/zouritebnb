<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResetPasswordInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Jeton reçu par email dans le lien de réinitialisation')]
        #[Assert\NotBlank]
        public string $token = '',
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Nouveau mot de passe (8 caractères minimum)', example: 'supersecret')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password = '',
    ) {
    }
}
