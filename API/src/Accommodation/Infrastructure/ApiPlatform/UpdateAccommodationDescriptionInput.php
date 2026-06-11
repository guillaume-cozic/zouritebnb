<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationDescriptionInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[Assert\Length(max: 255)]
        public string $title = '',
        #[Groups(['accommodation:write'])]
        public string $description = '',
    ) {
    }
}
