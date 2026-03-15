<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\Photo;
use App\Accommodation\Domain\Port\PhotoRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryPhotoRepository implements PhotoRepository
{
    /** @var Photo[] */
    private array $photos = [];

    public function save(Photo $photo): void
    {
        $this->photos[$photo->getId()->toRfc4122()] = $photo;
    }

    public function findById(Uuid $id): ?Photo
    {
        return $this->photos[$id->toRfc4122()] ?? null;
    }

    public function delete(Photo $photo): void
    {
        unset($this->photos[$photo->getId()->toRfc4122()]);
    }
}
