<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class IdentityVerified implements DomainEvent
{
    public function __construct(
        public Uuid $userId,
        public string $documentFilename,
        public string $documentContent,
        public string $selfieFilename,
        public string $selfieContent,
    ) {
    }
}
