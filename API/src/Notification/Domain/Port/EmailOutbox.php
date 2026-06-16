<?php

declare(strict_types=1);

namespace App\Notification\Domain\Port;

use App\Notification\Domain\Entity\OutboxEmail;
use Symfony\Component\Uid\Uuid;

interface EmailOutbox
{
    public function save(OutboxEmail $email): void;

    public function findById(Uuid $id): ?OutboxEmail;

    /**
     * Returns the oldest pending emails awaiting delivery, up to $limit.
     *
     * @return OutboxEmail[]
     */
    public function findPending(int $limit): array;
}
