<?php

declare(strict_types=1);

namespace App\User\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class SubmitIdentityDocumentsCommand
{
    public function __construct(
        public Uuid $userId,
        public string $documentType,
        public string $documentContent,
        public string $documentOriginalName,
        public string $documentMimeType,
        public int $documentSize,
        public string $selfieContent,
        public string $selfieOriginalName,
        public string $selfieMimeType,
        public int $selfieSize,
    ) {
    }
}
