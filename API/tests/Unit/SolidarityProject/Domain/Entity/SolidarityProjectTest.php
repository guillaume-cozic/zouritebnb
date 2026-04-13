<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SolidarityProjectTest extends TestCase
{
    public function testShouldCreateAValidSolidarityProject(): void
    {
        $id = Uuid::v7();
        $createdAt = new \DateTimeImmutable('2026-04-13T10:00:00+00:00');

        $project = new SolidarityProject(
            id: $id,
            title: 'Reforestation',
            description: 'Plant trees',
            imageUrl: 'https://example.com/image.jpg',
            status: 'active',
            createdAt: $createdAt,
        );

        self::assertSame($id, $project->getId());
        self::assertSame('Reforestation', $project->getTitle());
        self::assertSame('Plant trees', $project->getDescription());
        self::assertSame('https://example.com/image.jpg', $project->getImageUrl());
        self::assertSame('active', $project->getStatus());
        self::assertSame($createdAt, $project->getCreatedAt());
    }

    public function testShouldAcceptNullImageUrl(): void
    {
        $project = new SolidarityProject(
            id: Uuid::v7(),
            title: 'Title',
            description: 'Description',
            imageUrl: null,
            status: 'closed',
        );

        self::assertNull($project->getImageUrl());
        self::assertSame('closed', $project->getStatus());
    }

    public function testShouldAutoSetCreatedAtOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $project = new SolidarityProject(
            id: Uuid::v7(),
            title: 'Title',
            description: 'Description',
            imageUrl: null,
            status: 'active',
        );
        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $project->getCreatedAt());
        self::assertLessThanOrEqual($after, $project->getCreatedAt());
    }

    public function testShouldTrimTitleAndDescription(): void
    {
        $project = new SolidarityProject(
            id: Uuid::v7(),
            title: '  Title  ',
            description: '  Description  ',
            imageUrl: '  https://example.com/img.jpg  ',
            status: 'active',
        );

        self::assertSame('Title', $project->getTitle());
        self::assertSame('Description', $project->getDescription());
        self::assertSame('https://example.com/img.jpg', $project->getImageUrl());
    }

    public function testShouldThrowWhenTitleIsBlank(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            title: '   ',
            description: 'Description',
            imageUrl: null,
            status: 'active',
        );
    }

    public function testShouldThrowWhenDescriptionIsBlank(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            title: 'Title',
            description: '   ',
            imageUrl: null,
            status: 'active',
        );
    }

    public function testShouldThrowWhenStatusIsInvalid(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            title: 'Title',
            description: 'Description',
            imageUrl: null,
            status: 'pending',
        );
    }

    public function testShouldThrowWhenImageUrlIsBlank(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            title: 'Title',
            description: 'Description',
            imageUrl: '   ',
            status: 'active',
        );
    }
}
