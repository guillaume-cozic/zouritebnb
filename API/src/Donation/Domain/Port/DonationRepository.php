<?php

declare(strict_types=1);

namespace App\Donation\Domain\Port;

use App\Donation\Domain\Entity\Donation;
use Symfony\Component\Uid\Uuid;

interface DonationRepository
{
    public function save(Donation $donation): void;

    public function findById(Uuid $id): ?Donation;

    public function findByPaymentIntentId(string $paymentIntentId): ?Donation;
}
