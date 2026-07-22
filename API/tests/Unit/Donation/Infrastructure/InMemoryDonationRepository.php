<?php

declare(strict_types=1);

namespace App\Tests\Unit\Donation\Infrastructure;

use App\Donation\Domain\Entity\Donation;
use App\Donation\Domain\Port\DonationRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryDonationRepository implements DonationRepository
{
    /** @var Donation[] */
    private array $items = [];

    public function save(Donation $donation): void
    {
        $this->items[$donation->getId()->toRfc4122()] = $donation;
    }

    public function findById(Uuid $id): ?Donation
    {
        return $this->items[$id->toRfc4122()] ?? null;
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?Donation
    {
        foreach ($this->items as $donation) {
            if ($donation->getStripePaymentIntentId() === $paymentIntentId) {
                return $donation;
            }
        }

        return null;
    }

    /** @return Donation[] */
    public function all(): array
    {
        return array_values($this->items);
    }
}
