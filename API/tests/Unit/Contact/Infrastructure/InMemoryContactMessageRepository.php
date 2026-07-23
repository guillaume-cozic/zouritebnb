<?php

declare(strict_types=1);

namespace App\Tests\Unit\Contact\Infrastructure;

use App\Contact\Domain\Entity\ContactMessage;
use App\Contact\Domain\Port\ContactMessageRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryContactMessageRepository implements ContactMessageRepository
{
    /** @var ContactMessage[] */
    private array $messages = [];

    public function save(ContactMessage $message): void
    {
        $this->messages[$message->getId()->toRfc4122()] = $message;
    }

    public function findById(Uuid $id): ?ContactMessage
    {
        return $this->messages[$id->toRfc4122()] ?? null;
    }

    /** @return ContactMessage[] */
    public function all(): array
    {
        return array_values($this->messages);
    }
}
