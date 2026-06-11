<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Exception\InvalidKeyFigureException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KeyFigureTest extends TestCase
{
    public function test_should_create_a_valid_key_figure(): void
    {
        $keyFigure = new KeyFigure('10 000', 'arbres plantés');

        self::assertSame('10 000', $keyFigure->value());
        self::assertSame('arbres plantés', $keyFigure->label());
    }

    public function test_should_trim_value_and_label(): void
    {
        $keyFigure = new KeyFigure('  3 ans  ', '  de programme  ');

        self::assertSame('3 ans', $keyFigure->value());
        self::assertSame('de programme', $keyFigure->label());
    }

    #[DataProvider('provideInvalidKeyFigures')]
    public function test_should_throw_when_value_or_label_is_blank(?string $value, ?string $label): void
    {
        $this->expectException(InvalidKeyFigureException::class);

        new KeyFigure($value, $label);
    }

    public static function provideInvalidKeyFigures(): \Generator
    {
        yield 'null value' => [null, 'label'];
        yield 'blank value' => ['   ', 'label'];
        yield 'null label' => ['10 000', null];
        yield 'blank label' => ['10 000', '   '];
    }
}
