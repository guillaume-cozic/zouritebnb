<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Infrastructure;

use App\Review\Domain\Port\CompletedStay;
use App\Review\Domain\Port\CompletedStayChecker;
use Symfony\Component\Uid\Uuid;

final class InMemoryCompletedStayChecker implements CompletedStayChecker
{
    /** @var array<string, CompletedStay> */
    private array $stays = [];

    public function addCompletedStay(CompletedStay $stay): void
    {
        $this->stays[$this->key($stay->guestUserId, $stay->accommodationId)] = $stay;
    }

    public function hasCompletedStay(Uuid $guestUserId, Uuid $accommodationId): bool
    {
        return isset($this->stays[$this->key($guestUserId, $accommodationId)]);
    }

    public function findCompletedStay(Uuid $guestUserId, Uuid $accommodationId): ?CompletedStay
    {
        return $this->stays[$this->key($guestUserId, $accommodationId)] ?? null;
    }

    private function key(Uuid $guestUserId, Uuid $accommodationId): string
    {
        return $guestUserId->toRfc4122().'|'.$accommodationId->toRfc4122();
    }
}
