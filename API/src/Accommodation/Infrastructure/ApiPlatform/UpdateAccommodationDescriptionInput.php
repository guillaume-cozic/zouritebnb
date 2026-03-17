<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationDescriptionInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        public string $title = '',
        #[Groups(['accommodation:write'])]
        public string $description = '',
    ) {
    }
}
