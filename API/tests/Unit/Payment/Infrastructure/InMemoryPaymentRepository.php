<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Infrastructure;

use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Port\PaymentRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryPaymentRepository implements PaymentRepository
{
    /** @var Payment[] */
    private array $items = [];

    public function save(Payment $payment): void
    {
        $this->items[$payment->getId()->toRfc4122()] = $payment;
    }

    public function findById(Uuid $id): ?Payment
    {
        return $this->items[$id->toRfc4122()] ?? null;
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?Payment
    {
        foreach ($this->items as $payment) {
            if ($payment->getStripePaymentIntentId() === $paymentIntentId) {
                return $payment;
            }
        }

        return null;
    }

    public function findByReservationId(Uuid $reservationId): ?Payment
    {
        foreach ($this->items as $payment) {
            $linked = $payment->getReservationId();
            if (null !== $linked && $linked->equals($reservationId)) {
                return $payment;
            }
        }

        return null;
    }

    /** @return Payment[] */
    public function all(): array
    {
        return array_values($this->items);
    }
}
