<?php

declare(strict_types=1);

namespace App\Notification\Domain\Port;

use App\Notification\Domain\Entity\OutboxSms;
use Symfony\Component\Uid\Uuid;

interface SmsOutbox
{
    public function save(OutboxSms $sms): void;

    public function findById(Uuid $id): ?OutboxSms;

    /**
     * @return OutboxSms[]
     */
    public function findPending(int $limit): array;
}
