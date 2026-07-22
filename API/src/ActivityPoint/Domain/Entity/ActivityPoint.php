<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Entity;

use App\ActivityPoint\Domain\Exception\InvalidActivityPointException;
use Symfony\Component\Uid\Uuid;

final readonly class ActivityPoint
{
    private ?string $articleUrl;

    public function __construct(
        private Uuid $id,
        private string $name,
        private string $description,
        private ActivityPointCategory $category,
        private Coordinates $coordinates,
        ?string $articleUrl,
    ) {
        if ('' === trim($name)) {
            throw InvalidActivityPointException::becauseNameIsBlank();
        }

        if ('' === trim($description)) {
            throw InvalidActivityPointException::becauseDescriptionIsBlank();
        }

        if (null !== $articleUrl) {
            $articleUrl = trim($articleUrl);
            if ('' === $articleUrl) {
                throw InvalidActivityPointException::becauseArticleUrlIsBlank();
            }

            if (!str_starts_with($articleUrl, 'http://') && !str_starts_with($articleUrl, 'https://') && !str_starts_with($articleUrl, '/')) {
                throw InvalidActivityPointException::becauseArticleUrlIsInvalid($articleUrl);
            }
        }

        $this->articleUrl = $articleUrl;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategory(): ActivityPointCategory
    {
        return $this->category;
    }

    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }

    public function getArticleUrl(): ?string
    {
        return $this->articleUrl;
    }
}
