<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class InviteCoHostInput
{
    public function __construct(
        #[Groups(['team:write'])]
        #[ApiProperty(description: 'Adresse email du co-hôte à inviter', example: 'alice@example.com')]
        public ?string $email = null,
    ) {
    }
}
