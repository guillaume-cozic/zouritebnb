<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wishlist\Infrastructure;

use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\AccommodationSummaryProvider;
use Symfony\Component\Uid\Uuid;

final class InMemoryAccommodationSummaryProvider implements AccommodationSummaryProvider
{
    /** @var array<string, AccommodationSummary> */
    private array $summaries = [];

    public function add(Uuid $accommodationId, string $title = 'Test', ?string $city = 'Saint-Denis'): void
    {
        $this->summaries[$accommodationId->toRfc4122()] = new AccommodationSummary($accommodationId, $title, $city);
    }

    public function summaryOf(Uuid $accommodationId): ?AccommodationSummary
    {
        return $this->summaries[$accommodationId->toRfc4122()] ?? null;
    }
}
