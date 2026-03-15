<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\TooManyPhotosException;
use Symfony\Component\Uid\Uuid;

final class Gallery
{
    private const int MAX_PHOTOS = 20;

    /** @param Uuid[] $photoIds */
    public function __construct(
        private readonly Uuid $accommodationId,
        private array $photoIds = [],
    ) {
    }

    public function getAccommodationId(): Uuid
    {
        return $this->accommodationId;
    }

    public function addPhoto(Uuid $photoId): void
    {
        if (\count($this->photoIds) >= self::MAX_PHOTOS) {
            throw TooManyPhotosException::becauseMaxReached(self::MAX_PHOTOS);
        }

        $this->photoIds[] = $photoId;
    }

    public function removePhoto(Uuid $photoId): void
    {
        $this->photoIds = array_values(array_filter(
            $this->photoIds,
            static fn (Uuid $id) => !$id->equals($photoId),
        ));
    }

    /**
     * @param Uuid[] $orderedPhotoIds
     */
    public function reorder(array $orderedPhotoIds): void
    {
        $this->photoIds = $orderedPhotoIds;
    }

    /** @return Uuid[] */
    public function photoIds(): array
    {
        return $this->photoIds;
    }

    public function hasPhoto(Uuid $photoId): bool
    {
        foreach ($this->photoIds as $id) {
            if ($id->equals($photoId)) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return \count($this->photoIds);
    }
}
