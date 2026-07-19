<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SocialLoginInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Fournisseur d\'identité : "google", "apple" ou "facebook"', example: 'google')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['google', 'apple', 'facebook'])]
        public string $provider = '',
        #[Groups(['user:write'])]
        #[ApiProperty(description: 'Token émis par le fournisseur : ID token Google, identity token Apple ou access token Facebook', example: 'eyJhbGciOiJSUzI1NiIs...')]
        #[Assert\NotBlank]
        public string $token = '',
    ) {
    }
}
