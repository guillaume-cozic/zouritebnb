<?php

declare(strict_types=1);

namespace App\Payment\Domain\Port;

use App\Payment\Domain\Entity\Payment;
use Symfony\Component\Uid\Uuid;

interface PaymentRepository
{
    public function save(Payment $payment): void;

    public function findById(Uuid $id): ?Payment;

    public function findByPaymentIntentId(string $paymentIntentId): ?Payment;

    public function findByReservationId(Uuid $reservationId): ?Payment;
}
