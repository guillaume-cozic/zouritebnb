<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Entity\ProjectTranslation;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SolidarityProjectTest extends TestCase
{
    public function test_should_create_a_valid_solidarity_project(): void
    {
        $id = Uuid::v7();
        $createdAt = new \DateTimeImmutable('2026-04-13T10:00:00+00:00');

        $project = new SolidarityProject(
            id: $id,
            translations: ['fr' => new ProjectTranslation('Reforestation', 'Plant trees')],
            imageUrl: 'https://example.com/image.jpg',
            status: 'active',
            createdAt: $createdAt,
        );

        self::assertSame($id, $project->getId());
        self::assertSame('Reforestation', $project->translation('fr')->getTitle());
        self::assertSame('Plant trees', $project->translation('fr')->getDescription());
        self::assertSame('https://example.com/image.jpg', $project->getImageUrl());
        self::assertSame('active', $project->getStatus());
        self::assertSame($createdAt, $project->getCreatedAt());
    }

    public function test_should_serve_a_translation_for_each_supported_locale(): void
    {
        $project = new SolidarityProject(
            id: Uuid::v7(),
            translations: [
                'fr' => new ProjectTranslation('Reforestation', 'Planter des arbres'),
                'en' => new ProjectTranslation('Reforestation', 'Plant trees'),
            ],
            imageUrl: null,
            status: 'active',
        );

        self::assertSame('Planter des arbres', $project->translation('fr')->getDescription());
        self::assertSame('Plant trees', $project->translation('en')->getDescription());
    }

    public function test_should_fall_back_to_default_locale_when_translation_is_missing(): void
    {
        $project = new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Reforestation', 'Planter des arbres')],
            imageUrl: null,
            status: 'active',
        );

        self::assertSame('Planter des arbres', $project->translation('en')->getDescription());
    }

    public function test_should_accept_null_image_url(): void
    {
        $project = new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Title', 'Description')],
            imageUrl: null,
            status: 'closed',
        );

        self::assertNull($project->getImageUrl());
        self::assertSame('closed', $project->getStatus());
    }

    public function test_should_auto_set_created_at_on_construction(): void
    {
        $before = new \DateTimeImmutable();
        $project = new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Title', 'Description')],
            imageUrl: null,
            status: 'active',
        );
        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $project->getCreatedAt());
        self::assertLessThanOrEqual($after, $project->getCreatedAt());
    }

    public function test_should_trim_image_url(): void
    {
        $project = new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Title', 'Description')],
            imageUrl: '  https://example.com/img.jpg  ',
            status: 'active',
        );

        self::assertSame('https://example.com/img.jpg', $project->getImageUrl());
    }

    public function test_should_throw_when_default_translation_is_missing(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            translations: ['en' => new ProjectTranslation('Title', 'Description')],
            imageUrl: null,
            status: 'active',
        );
    }

    public function test_should_throw_when_locale_is_unsupported(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            translations: [
                'fr' => new ProjectTranslation('Title', 'Description'),
                'de' => new ProjectTranslation('Titel', 'Beschreibung'),
            ],
            imageUrl: null,
            status: 'active',
        );
    }

    public function test_should_throw_when_status_is_invalid(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Title', 'Description')],
            imageUrl: null,
            status: 'pending',
        );
    }

    public function test_should_throw_when_image_url_is_blank(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Title', 'Description')],
            imageUrl: '   ',
            status: 'active',
        );
    }
}
