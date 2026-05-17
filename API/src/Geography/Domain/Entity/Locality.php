<?php

declare(strict_types=1);

namespace App\Geography\Domain\Entity;

use App\Geography\Domain\Exception\InvalidLocalityException;
use Symfony\Component\Uid\Uuid;

final readonly class Locality
{
    private string $name;

    public function __construct(
        private Uuid $id,
        string $name,
        private Uuid $regionId,
    ) {
        $name = trim($name);
        if ('' === $name) {
            throw InvalidLocalityException::becauseNameBlank();
        }

        $this->name = $name;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegionId(): Uuid
    {
        return $this->regionId;
    }
}
