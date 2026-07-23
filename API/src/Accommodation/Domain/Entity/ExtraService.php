<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidExtraServiceException;

/**
 * A paid extra service offered with an accommodation (e.g. cleaning, breakfast) —
 * a named option with a fixed price the guest can add to a stay.
 */
final readonly class ExtraService
{
    public const int NAME_MAX_LENGTH = 100;

    public string $name;

    public function __construct(
        string $name,
        public float $price,
        public bool $billedWithReservation = false,
    ) {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidExtraServiceException::becauseEmptyName();
        }
        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidExtraServiceException::becauseNameTooLong(mb_strlen($name), self::NAME_MAX_LENGTH);
        }
        if ($price <= 0) {
            throw InvalidExtraServiceException::becauseNonPositivePrice($price);
        }

        $this->name = $name;
    }

    public static function fromArray(array $data): self
    {
        $billedWithReservation = $data['billedWithReservation'] ?? false;
        if (!\is_bool($billedWithReservation)) {
            throw InvalidExtraServiceException::becauseNonBooleanBilledWithReservation();
        }

        return new self(
            name: (string) ($data['name'] ?? ''),
            price: (float) ($data['price'] ?? 0),
            billedWithReservation: $billedWithReservation,
        );
    }

    /** @return array{name: string, price: float, billedWithReservation: bool} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'billedWithReservation' => $this->billedWithReservation,
        ];
    }
}
