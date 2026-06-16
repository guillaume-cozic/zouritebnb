<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Notification\Domain\Entity\OutboxSms;
use App\Notification\Domain\Entity\OutboxStatus;
use App\Notification\Domain\Port\SmsOutbox;
use Symfony\Component\Uid\Uuid;

final class InMemorySmsOutbox implements SmsOutbox
{
    /** @var array<string, OutboxSms> */
    private array $messages = [];

    public function save(OutboxSms $sms): void
    {
        $this->messages[$sms->getId()->toRfc4122()] = $sms;
    }

    public function findById(Uuid $id): ?OutboxSms
    {
        return $this->messages[$id->toRfc4122()] ?? null;
    }

    public function findPending(int $limit): array
    {
        $pending = array_filter(
            $this->messages,
            static fn (OutboxSms $sms): bool => OutboxStatus::Pending === $sms->getStatus(),
        );

        return \array_slice(array_values($pending), 0, $limit);
    }

    /**
     * @return OutboxSms[]
     */
    public function all(): array
    {
        return array_values($this->messages);
    }
}
