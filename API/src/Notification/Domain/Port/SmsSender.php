<?php

declare(strict_types=1);

namespace App\Notification\Domain\Port;

use App\Notification\Domain\Entity\SmsMessage;
use App\Notification\Domain\Exception\SmsDeliveryException;

interface SmsSender
{
    /**
     * Actually delivers the SMS through the gateway.
     *
     * @throws SmsDeliveryException when delivery fails (the relay records the attempt and retries)
     */
    public function send(SmsMessage $sms): void;
}
