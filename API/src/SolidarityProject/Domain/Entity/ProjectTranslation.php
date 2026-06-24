<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectException;

/**
 * The translatable content of a solidarity project for a single locale: the title,
 * the description and the key figures. A {@see SolidarityProject} holds one of these
 * per supported locale.
 */
final readonly class ProjectTranslation
{
    private string $title;
    private string $description;

    /**
     * @param KeyFigure[] $keyFigures
     */
    public function __construct(
        string $title,
        string $description,
        private array $keyFigures = [],
    ) {
        $title = trim($title);
        if ('' === $title) {
            throw InvalidSolidarityProjectException::becauseTitleIsBlank();
        }

        $description = trim($description);
        if ('' === $description) {
            throw InvalidSolidarityProjectException::becauseDescriptionIsBlank();
        }

        $this->title = $title;
        $this->description = $description;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return KeyFigure[]
     */
    public function getKeyFigures(): array
    {
        return $this->keyFigures;
    }
}
