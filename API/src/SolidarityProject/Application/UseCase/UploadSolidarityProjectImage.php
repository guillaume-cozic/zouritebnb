<?php

declare(strict_types=1);

namespace App\SolidarityProject\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\SolidarityProject\Domain\Command\UploadSolidarityProjectImageCommand;
use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectImageException;
use App\SolidarityProject\Domain\Port\SolidarityProjectImageStorage;
use App\SolidarityProject\Domain\Port\SolidarityProjectImageTransformer;

final readonly class UploadSolidarityProjectImage
{
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Hard cap on the uploaded image size (10 MB) to prevent memory-exhaustion DoS. */
    private const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private SolidarityProjectImageStorage $storage,
        private SolidarityProjectImageTransformer $imageTransformer,
    ) {
    }

    /**
     * Recompresses the uploaded image (WebP, hero-sized), stores it and
     * returns the generated filename.
     */
    public function handle(UploadSolidarityProjectImageCommand $command): string
    {
        if (!\in_array($command->mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidSolidarityProjectImageException::becauseInvalidMimeType($command->mimeType);
        }

        if ($command->size > self::MAX_SIZE_BYTES) {
            throw InvalidSolidarityProjectImageException::becauseTooLarge($command->size, self::MAX_SIZE_BYTES);
        }

        $filename = UuidGenerator::generate()->toRfc4122().'.webp';

        $this->storage->store($filename, $this->imageTransformer->toHeroWebp($command->content));

        return $filename;
    }
}
