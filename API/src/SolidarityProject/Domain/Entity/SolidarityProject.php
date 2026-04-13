<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectException;
use Symfony\Component\Uid\Uuid;

final readonly class SolidarityProject
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    private const ALLOWED_STATUSES = [self::STATUS_ACTIVE, self::STATUS_CLOSED];

    private string $title;
    private string $description;
    private ?string $imageUrl;

    public function __construct(
        private Uuid $id,
        string $title,
        string $description,
        ?string $imageUrl,
        private string $status,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
        $title = trim($title);
        if ('' === $title) {
            throw InvalidSolidarityProjectException::becauseTitleIsBlank();
        }

        $description = trim($description);
        if ('' === $description) {
            throw InvalidSolidarityProjectException::becauseDescriptionIsBlank();
        }

        if (!\in_array($status, self::ALLOWED_STATUSES, true)) {
            throw InvalidSolidarityProjectException::becauseStatusIsInvalid($status);
        }

        if (null !== $imageUrl) {
            $imageUrl = trim($imageUrl);
            if ('' === $imageUrl) {
                throw InvalidSolidarityProjectException::becauseImageUrlIsBlank();
            }
        }

        $this->title = $title;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
