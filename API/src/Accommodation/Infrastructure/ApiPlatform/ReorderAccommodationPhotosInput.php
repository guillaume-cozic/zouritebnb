<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReorderAccommodationPhotosInput
{
    /**
     * @param string[] $photoIds
     */
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\NotBlank(),
            new Assert\Uuid(),
        ])]
        public array $photoIds = [],
    ) {
    }
}
