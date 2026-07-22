<?php

declare(strict_types=1);

namespace App\Tests\Unit\Donation\Infrastructure;

use App\Shared\Domain\Port\SolidarityProjectDonationChecker;
use Symfony\Component\Uid\Uuid;

final class InMemorySolidarityProjectDonationChecker implements SolidarityProjectDonationChecker
{
    /** @var array<string, true> */
    private array $activeProjectIds = [];

    public function activate(Uuid $solidarityProjectId): void
    {
        $this->activeProjectIds[$solidarityProjectId->toRfc4122()] = true;
    }

    public function isActive(Uuid $solidarityProjectId): bool
    {
        return isset($this->activeProjectIds[$solidarityProjectId->toRfc4122()]);
    }
}
