<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\SolidarityProject\Application\UseCase\UploadSolidarityProjectImage;
use App\SolidarityProject\Domain\Command\UploadSolidarityProjectImageCommand;
use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectImageException;
use App\SolidarityProject\Domain\Port\SolidarityProjectImageStorage;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UploadSolidarityProjectImageTest extends TestCase
{
    /** @var SolidarityProjectImageStorage&object{stored: array<string, string>} */
    private object $storage;
    private UploadSolidarityProjectImage $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->storage = new class implements SolidarityProjectImageStorage {
            /** @var array<string, string> */
            public array $stored = [];

            public function store(string $filename, string $content): void
            {
                $this->stored[$filename] = $content;
            }
        };

        $this->useCase = new UploadSolidarityProjectImage($this->storage);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_store_image_with_extension_from_mime_type(): void
    {
        UuidGenerator::freeze(Uuid::fromString('01961e2f-dead-7000-beef-000000000001'));

        $filename = $this->useCase->handle(new UploadSolidarityProjectImageCommand(
            content: 'binary-bytes',
            mimeType: 'image/png',
            size: 1024,
        ));

        self::assertSame('01961e2f-dead-7000-beef-000000000001.png', $filename);
        self::assertSame('binary-bytes', $this->storage->stored[$filename]);
    }

    public function test_should_reject_an_unsupported_mime_type(): void
    {
        $this->expectException(InvalidSolidarityProjectImageException::class);

        $this->useCase->handle(new UploadSolidarityProjectImageCommand(
            content: 'x',
            mimeType: 'application/pdf',
            size: 10,
        ));
    }

    public function test_should_reject_a_file_that_is_too_large(): void
    {
        $this->expectException(InvalidSolidarityProjectImageException::class);

        $this->useCase->handle(new UploadSolidarityProjectImageCommand(
            content: 'x',
            mimeType: 'image/jpeg',
            size: 11 * 1024 * 1024,
        ));
    }
}
