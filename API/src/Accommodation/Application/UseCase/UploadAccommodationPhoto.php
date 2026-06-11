<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UploadAccommodationPhotoCommand;
use App\Accommodation\Domain\Event\AccommodationPhotoUploaded;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidPhotoException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Accommodation\Domain\Port\GalleryRepository;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class UploadAccommodationPhoto
{
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Hard cap on the uploaded photo size (10 MB) to prevent memory-exhaustion DoS. */
    private const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private AccommodationRepository $accommodationRepository,
        private GalleryRepository $galleryRepository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UploadAccommodationPhotoCommand $command): void
    {
        $accommodation = $this->accommodationRepository->findById($command->accommodationId);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->accommodationId->toRfc4122());
        }

        if (!\in_array($command->mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidPhotoException::becauseInvalidMimeType($command->mimeType);
        }

        if ($command->size > self::MAX_SIZE_BYTES) {
            throw InvalidPhotoException::becauseTooLarge($command->size, self::MAX_SIZE_BYTES);
        }

        $gallery = $this->galleryRepository->findByAccommodationId($command->accommodationId);

        $photoId = UuidGenerator::generate();
        $gallery->addPhoto($photoId);

        $this->galleryRepository->save($gallery);

        $this->eventBus->dispatch([new AccommodationPhotoUploaded(
            accommodationId: $command->accommodationId,
            photoId: $photoId,
            content: $command->content,
            originalName: $command->originalName,
            mimeType: $command->mimeType,
            size: $command->size,
        )]);
    }
}
