<?php

declare(strict_types=1);

namespace App\Tests\Unit\Geography\Domain\Entity;

use App\Geography\Domain\Entity\Region;
use App\Geography\Domain\Exception\InvalidRegionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RegionTest extends TestCase
{
    public function test_should_create_a_valid_region(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $region = new Region(id: $id, code: 'IDF', name: 'Île-de-France');

        self::assertSame($id, $region->getId());
        self::assertSame('IDF', $region->getCode());
        self::assertSame('Île-de-France', $region->getName());
    }

    #[DataProvider('validCodeProvider')]
    public function test_should_accept_valid_codes(string $code): void
    {
        $region = new Region(id: Uuid::v7(), code: $code, name: 'Region');

        self::assertSame($code, $region->getCode());
    }

    public static function validCodeProvider(): \Generator
    {
        yield 'two letters' => ['FR'];
        yield 'letter and digit' => ['A1'];
        yield 'with underscore' => ['A_B'];
        yield 'max length (31 chars)' => ['A'.str_repeat('B', 30)];
        yield 'letters digits underscores' => ['REGION_01'];
    }

    public function test_should_trim_name(): void
    {
        $region = new Region(id: Uuid::v7(), code: 'IDF', name: '  Île-de-France  ');

        self::assertSame('Île-de-France', $region->getName());
    }

    #[DataProvider('invalidCodeProvider')]
    public function test_should_throw_when_code_is_invalid(string $code): void
    {
        $this->expectException(InvalidRegionException::class);
        $this->expectExceptionMessage(\sprintf('Region code "%s" is invalid.', $code));

        new Region(id: Uuid::v7(), code: $code, name: 'Region');
    }

    public static function invalidCodeProvider(): \Generator
    {
        yield 'empty' => [''];
        yield 'single letter (too short)' => ['A'];
        yield 'starts with digit' => ['1AB'];
        yield 'starts with underscore' => ['_AB'];
        yield 'lowercase' => ['idf'];
        yield 'contains lowercase' => ['IDf'];
        yield 'contains space' => ['ID F'];
        yield 'contains hyphen' => ['ID-F'];
        yield 'too long (32 chars)' => ['A'.str_repeat('B', 31)];
    }

    public function test_should_throw_when_name_is_blank(): void
    {
        $this->expectException(InvalidRegionException::class);
        $this->expectExceptionMessage('Region name must not be blank.');

        new Region(id: Uuid::v7(), code: 'IDF', name: '   ');
    }

    public function test_should_throw_when_name_is_empty(): void
    {
        $this->expectException(InvalidRegionException::class);
        $this->expectExceptionMessage('Region name must not be blank.');

        new Region(id: Uuid::v7(), code: 'IDF', name: '');
    }
}
