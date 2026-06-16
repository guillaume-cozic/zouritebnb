<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

enum OutboxStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
