<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Shared\Domain\Port\ReservationSummary;
use App\Shared\Domain\Port\ReservationSummaryProvider;
use Symfony\Component\Uid\Uuid;

final class InMemoryReservationSummaryProvider implements ReservationSummaryProvider
{
    /** @var array<string, ReservationSummary> */
    private array $summaries = [];

    public function add(ReservationSummary $summary): void
    {
        $this->summaries[$summary->reservationId->toRfc4122()] = $summary;
    }

    public function findById(Uuid $reservationId): ?ReservationSummary
    {
        return $this->summaries[$reservationId->toRfc4122()] ?? null;
    }
}
