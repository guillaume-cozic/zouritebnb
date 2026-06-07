<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\ReorderAccommodationPhotos;
use App\Accommodation\Domain\Command\ReorderAccommodationPhotosCommand;
use App\Accommodation\Domain\Entity\Gallery;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryGalleryRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReorderAccommodationPhotosTest extends TestCase
{
    private InMemoryGalleryRepository $galleryRepository;
    private ReorderAccommodationPhotos $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->galleryRepository = new InMemoryGalleryRepository();
        $this->useCase = new ReorderAccommodationPhotos($this->galleryRepository);
    }

    public function test_should_reorder_photos(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoA = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $photoB = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b2');
        $photoC = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c3');

        $this->galleryRepository->save(new Gallery(
            accommodationId: $accommodationId,
            photoIds: [$photoA, $photoB, $photoC],
        ));

        $this->useCase->handle(new ReorderAccommodationPhotosCommand(
            accommodationId: $accommodationId,
            photoIds: [$photoC, $photoA, $photoB],
        ));

        $gallery = $this->galleryRepository->findByAccommodationId($accommodationId);
        $orderedIds = array_map(static fn (Uuid $id) => $id->toRfc4122(), $gallery->photoIds());
        self::assertSame(
            [$photoC->toRfc4122(), $photoA->toRfc4122(), $photoB->toRfc4122()],
            $orderedIds,
        );
    }

    public function test_should_reorder_empty_gallery_when_none_existing(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->useCase->handle(new ReorderAccommodationPhotosCommand(
            accommodationId: $accommodationId,
            photoIds: [],
        ));

        $gallery = $this->galleryRepository->findByAccommodationId($accommodationId);
        self::assertSame(0, $gallery->count());
    }
}
