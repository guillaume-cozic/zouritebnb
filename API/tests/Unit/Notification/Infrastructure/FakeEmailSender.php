<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Exception\EmailDeliveryException;
use App\Notification\Domain\Port\EmailSender;

final class FakeEmailSender implements EmailSender
{
    /** @var OutboxEmail[] */
    public array $sent = [];

    public function __construct(private readonly bool $shouldFail = false)
    {
    }

    public function send(OutboxEmail $email): void
    {
        if ($this->shouldFail) {
            throw EmailDeliveryException::because('transport down');
        }

        $this->sent[] = $email;
    }
}
