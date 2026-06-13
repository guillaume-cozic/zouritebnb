<?php

declare(strict_types=1);

namespace App\SolidarityProject\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\SolidarityProject\Domain\Command\UploadSolidarityProjectImageCommand;
use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectImageException;
use App\SolidarityProject\Domain\Port\SolidarityProjectImageStorage;

final readonly class UploadSolidarityProjectImage
{
    /** @var array<string, string> MIME type => file extension */
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Hard cap on the uploaded image size (10 MB) to prevent memory-exhaustion DoS. */
    private const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private SolidarityProjectImageStorage $storage,
    ) {
    }

    /**
     * Stores the uploaded image and returns the generated filename.
     */
    public function handle(UploadSolidarityProjectImageCommand $command): string
    {
        if (!isset(self::ALLOWED_MIME_TYPES[$command->mimeType])) {
            throw InvalidSolidarityProjectImageException::becauseInvalidMimeType($command->mimeType);
        }

        if ($command->size > self::MAX_SIZE_BYTES) {
            throw InvalidSolidarityProjectImageException::becauseTooLarge($command->size, self::MAX_SIZE_BYTES);
        }

        $filename = UuidGenerator::generate()->toRfc4122().'.'.self::ALLOWED_MIME_TYPES[$command->mimeType];

        $this->storage->store($filename, $command->content);

        return $filename;
    }
}
