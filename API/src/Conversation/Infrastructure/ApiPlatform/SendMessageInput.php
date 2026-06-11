<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SendMessageInput
{
    public function __construct(
        #[Groups(['conversation:write'])]
        #[ApiProperty(description: 'Corps du message. Doit contenir au moins un caractère non blanc et pas plus de 5000 caractères.', example: 'Bonjour, est-il possible d\'avoir un lit bébé ?')]
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 5000)]
        public string $body = '',
    ) {
    }
}
