<?php

declare(strict_types=1);

namespace App\Notification\Domain\Port;

use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Exception\EmailDeliveryException;

interface EmailSender
{
    /**
     * Actually delivers the email through the mail transport (SMTP, API…).
     *
     * @throws EmailDeliveryException when delivery fails (the relay records the attempt and retries)
     */
    public function send(OutboxEmail $email): void;
}
