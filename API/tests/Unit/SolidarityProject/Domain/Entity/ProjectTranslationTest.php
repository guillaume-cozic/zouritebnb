<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\ProjectTranslation;
use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectException;
use PHPUnit\Framework\TestCase;

final class ProjectTranslationTest extends TestCase
{
    public function test_should_trim_title_and_description(): void
    {
        $translation = new ProjectTranslation('  Title  ', '  Description  ');

        self::assertSame('Title', $translation->getTitle());
        self::assertSame('Description', $translation->getDescription());
    }

    public function test_should_keep_key_figures(): void
    {
        $translation = new ProjectTranslation('Title', 'Description', [new KeyFigure('10 000', 'arbres')]);

        self::assertCount(1, $translation->getKeyFigures());
        self::assertSame('arbres', $translation->getKeyFigures()[0]->label());
    }

    public function test_should_throw_when_title_is_blank(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new ProjectTranslation('   ', 'Description');
    }

    public function test_should_throw_when_description_is_blank(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        new ProjectTranslation('Title', '   ');
    }
}
