<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Exception\InvalidReservationIdException;
use Symfony\Component\Uid\Uuid;

final readonly class ReservationId
{
    public function __construct(private ?Uuid $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value) {
            throw InvalidReservationIdException::becauseNull();
        }
    }

    public function toString(): string
    {
        return $this->value->toRfc4122();
    }

    public function toUuid(): Uuid
    {
        return $this->value;
    }
}
