<?php

declare(strict_types=1);

namespace App\Geography\Domain\Entity;

use App\Geography\Domain\Exception\InvalidRegionException;
use Symfony\Component\Uid\Uuid;

final readonly class Region
{
    private const CODE_PATTERN = '/^[A-Z][A-Z0-9_]{1,30}$/';

    private string $code;
    private string $name;

    public function __construct(
        private Uuid $id,
        string $code,
        string $name,
    ) {
        if (1 !== preg_match(self::CODE_PATTERN, $code)) {
            throw InvalidRegionException::becauseCodeInvalid($code);
        }

        $name = trim($name);
        if ('' === $name) {
            throw InvalidRegionException::becauseNameBlank();
        }

        $this->code = $code;
        $this->name = $name;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
