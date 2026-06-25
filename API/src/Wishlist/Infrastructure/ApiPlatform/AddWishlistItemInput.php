<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddWishlistItemInput
{
    public function __construct(
        #[Groups(['wishlist:write'])]
        #[ApiProperty(description: 'Identifiant UUID de l\'hébergement à ajouter à la wishlist', example: '01961e2f-dead-7000-beef-000000000001')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $accommodationId = '',
    ) {
    }
}
