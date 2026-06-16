<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Notification\Domain\Entity\SmsMessage;
use App\Notification\Domain\Exception\SmsDeliveryException;
use App\Notification\Domain\Port\SmsSender;

final class FakeSmsSender implements SmsSender
{
    /** @var SmsMessage[] */
    public array $sent = [];

    public function __construct(private readonly bool $shouldFail = false)
    {
    }

    public function send(SmsMessage $sms): void
    {
        if ($this->shouldFail) {
            throw SmsDeliveryException::because('gateway down');
        }

        $this->sent[] = $sms;
    }
}
