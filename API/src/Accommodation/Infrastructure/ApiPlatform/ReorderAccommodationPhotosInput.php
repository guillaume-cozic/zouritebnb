<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class ReorderAccommodationPhotosInput
{
    /**
     * @param string[] $photoIds
     */
    public function __construct(
        #[Groups(['accommodation:write'])]
        public array $photoIds = [],
    ) {
    }
}
