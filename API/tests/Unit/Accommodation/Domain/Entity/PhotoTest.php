<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\Photo;
use App\Accommodation\Domain\Exception\InvalidPhotoException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PhotoTest extends TestCase
{
    public function test_should_create_valid_photo(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $photo = new Photo(
            id: $id,
            accommodationId: $accommodationId,
            filename: 'abc.jpg',
            originalName: 'beach.jpg',
            mimeType: 'image/jpeg',
            size: 12345,
        );

        self::assertTrue($id->equals($photo->getId()));
        self::assertTrue($accommodationId->equals($photo->getAccommodationId()));
        self::assertSame('abc.jpg', $photo->getFilename());
        self::assertSame('beach.jpg', $photo->getOriginalName());
        self::assertSame('image/jpeg', $photo->getMimeType());
        self::assertSame(12345, $photo->getSize());
    }

    #[DataProvider('allowedMimeTypeProvider')]
    public function test_should_accept_allowed_mime_types(string $mimeType): void
    {
        $photo = new Photo(
            id: Uuid::fromString('01961e2f-dead-7000-beef-000000000010'),
            accommodationId: Uuid::fromString('01961e2f-dead-7000-beef-000000000001'),
            filename: 'abc',
            originalName: 'beach',
            mimeType: $mimeType,
            size: 1,
        );

        self::assertSame($mimeType, $photo->getMimeType());
    }

    public static function allowedMimeTypeProvider(): \Generator
    {
        yield 'jpeg' => ['image/jpeg'];
        yield 'png' => ['image/png'];
        yield 'webp' => ['image/webp'];
    }

    public function test_should_throw_when_mime_type_is_not_allowed(): void
    {
        $this->expectException(InvalidPhotoException::class);
        $this->expectExceptionMessage('Only JPEG, PNG and WebP images are allowed, got image/gif.');

        new Photo(
            id: Uuid::fromString('01961e2f-dead-7000-beef-000000000010'),
            accommodationId: Uuid::fromString('01961e2f-dead-7000-beef-000000000001'),
            filename: 'abc.gif',
            originalName: 'beach.gif',
            mimeType: 'image/gif',
            size: 1,
        );
    }
}
