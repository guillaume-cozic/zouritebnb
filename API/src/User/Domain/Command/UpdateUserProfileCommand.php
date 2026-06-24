<?php

declare(strict_types=1);

namespace App\User\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateUserProfileCommand
{
    public function __construct(
        public Uuid $id,
        public ?string $firstName,
        public ?string $lastName,
        public string $email,
        public ?string $bio = null,
    ) {
    }
}
