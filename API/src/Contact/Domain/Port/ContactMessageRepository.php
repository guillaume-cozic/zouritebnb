<?php

declare(strict_types=1);

namespace App\Contact\Domain\Port;

use App\Contact\Domain\Entity\ContactMessage;
use Symfony\Component\Uid\Uuid;

interface ContactMessageRepository
{
    public function save(ContactMessage $message): void;

    public function findById(Uuid $id): ?ContactMessage;
}
