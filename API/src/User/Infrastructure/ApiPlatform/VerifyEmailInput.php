<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class VerifyEmailInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Jeton reçu par email dans le lien de vérification')]
        #[Assert\NotBlank]
        public string $token = '',
    ) {
    }
}
