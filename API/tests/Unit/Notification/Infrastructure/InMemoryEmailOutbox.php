<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Notification\Domain\Entity\EmailStatus;
use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Port\EmailOutbox;
use Symfony\Component\Uid\Uuid;

final class InMemoryEmailOutbox implements EmailOutbox
{
    /** @var array<string, OutboxEmail> */
    private array $emails = [];

    public function save(OutboxEmail $email): void
    {
        $this->emails[$email->getId()->toRfc4122()] = $email;
    }

    public function findById(Uuid $id): ?OutboxEmail
    {
        return $this->emails[$id->toRfc4122()] ?? null;
    }

    public function findPending(int $limit): array
    {
        $pending = array_filter(
            $this->emails,
            static fn (OutboxEmail $email): bool => EmailStatus::Pending === $email->getStatus(),
        );

        return \array_slice(array_values($pending), 0, $limit);
    }

    /**
     * @return OutboxEmail[]
     */
    public function all(): array
    {
        return array_values($this->emails);
    }
}
