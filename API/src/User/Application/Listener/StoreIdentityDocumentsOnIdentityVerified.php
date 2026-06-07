<?php

declare(strict_types=1);

namespace App\User\Application\Listener;

use App\User\Domain\Event\IdentityVerified;
use App\User\Domain\Port\IdentityDocumentStorage;

final readonly class StoreIdentityDocumentsOnIdentityVerified
{
    public function __construct(
        private IdentityDocumentStorage $storage,
    ) {
    }

    public function __invoke(IdentityVerified $event): void
    {
        $this->storage->store($event->documentFilename, $event->documentContent);
        $this->storage->store($event->selfieFilename, $event->selfieContent);
    }
}
