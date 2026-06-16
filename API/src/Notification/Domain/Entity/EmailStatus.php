<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

enum EmailStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
