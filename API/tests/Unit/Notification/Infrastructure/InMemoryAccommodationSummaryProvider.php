<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\AccommodationSummaryProvider;
use Symfony\Component\Uid\Uuid;

final class InMemoryAccommodationSummaryProvider implements AccommodationSummaryProvider
{
    /** @var array<string, AccommodationSummary> */
    private array $summaries = [];

    public function add(AccommodationSummary $summary): void
    {
        $this->summaries[$summary->accommodationId->toRfc4122()] = $summary;
    }

    public function summaryOf(Uuid $accommodationId): ?AccommodationSummary
    {
        return $this->summaries[$accommodationId->toRfc4122()] ?? null;
    }
}
