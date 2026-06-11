<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\IdentityDocument;
use App\User\Domain\Exception\InvalidIdentityDocumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentityDocumentTest extends TestCase
{
    public function test_should_create_a_valid_document(): void
    {
        $document = new IdentityDocument(
            content: 'binary-bytes',
            originalName: 'passport.jpg',
            mimeType: 'image/jpeg',
            size: 1024,
        );

        self::assertSame('binary-bytes', $document->getContent());
        self::assertSame('passport.jpg', $document->getOriginalName());
        self::assertSame('image/jpeg', $document->getMimeType());
        self::assertSame(1024, $document->getSize());
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function mimeToExtensionProvider(): \Generator
    {
        yield 'jpeg' => ['image/jpeg', 'jpg'];
        yield 'png' => ['image/png', 'png'];
        yield 'webp' => ['image/webp', 'webp'];
    }

    #[DataProvider('mimeToExtensionProvider')]
    public function test_should_derive_extension_from_mime_type(string $mimeType, string $expectedExtension): void
    {
        $document = new IdentityDocument(
            content: 'x',
            originalName: 'doc',
            mimeType: $mimeType,
            size: 1,
        );

        self::assertSame($expectedExtension, $document->extension());
    }

    public function test_should_reject_an_invalid_mime_type(): void
    {
        $this->expectException(InvalidIdentityDocumentException::class);

        new IdentityDocument(
            content: 'x',
            originalName: 'doc.pdf',
            mimeType: 'application/pdf',
            size: 1,
        );
    }

    public function test_should_reject_a_document_larger_than_the_max_size(): void
    {
        $this->expectException(InvalidIdentityDocumentException::class);

        new IdentityDocument(
            content: 'x',
            originalName: 'doc.jpg',
            mimeType: 'image/jpeg',
            size: 10 * 1024 * 1024 + 1,
        );
    }

    public function test_should_accept_a_document_at_the_max_size(): void
    {
        $document = new IdentityDocument(
            content: 'x',
            originalName: 'doc.jpg',
            mimeType: 'image/jpeg',
            size: 10 * 1024 * 1024,
        );

        self::assertSame(10 * 1024 * 1024, $document->getSize());
    }
}
