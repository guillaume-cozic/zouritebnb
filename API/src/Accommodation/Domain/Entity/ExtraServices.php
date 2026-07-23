<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidExtraServiceException;

/**
 * Ordered collection of extra services for an accommodation.
 */
final readonly class ExtraServices
{
    /** @var ExtraService[] */
    private array $services;

    /**
     * @param ExtraService[] $services
     */
    public function __construct(array $services)
    {
        foreach ($services as $service) {
            if (!$service instanceof ExtraService) {
                throw InvalidExtraServiceException::becauseInvalidItem();
            }
        }

        $this->services = array_values($services);
    }

    /**
     * @param array<array{name?: string, price?: float}> $list
     */
    public static function fromArray(array $list): self
    {
        return new self(array_map(
            static fn (array $data): ExtraService => ExtraService::fromArray($data),
            array_values($list),
        ));
    }

    /** @return array<array{name: string, price: float}> */
    public function toArray(): array
    {
        return array_map(static fn (ExtraService $service): array => $service->toArray(), $this->services);
    }

    public function isEmpty(): bool
    {
        return [] === $this->services;
    }
}
