<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\Address;
use App\Accommodation\Domain\Exception\InvalidAddressException;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function test_should_create_a_valid_address(): void
    {
        $address = new Address(
            street: '1 rue de la Paix',
            city: 'Paris',
            zipCode: '75002',
            country: 'France',
        );

        self::assertSame('1 rue de la Paix', $address->street());
        self::assertSame('Paris', $address->city());
        self::assertSame('75002', $address->zipCode());
        self::assertSame('France', $address->country());
    }

    public function test_should_accept_empty_zip_code(): void
    {
        $address = new Address(
            street: '1 rue de la Paix',
            city: 'Paris',
            zipCode: '',
            country: 'France',
        );

        self::assertSame('', $address->zipCode());
    }

    public function test_should_throw_when_street_is_blank(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Street is required.');

        new Address(street: '   ', city: 'Paris', zipCode: '75002', country: 'France');
    }

    public function test_should_throw_when_city_is_blank(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('City is required.');

        new Address(street: '1 rue de la Paix', city: '   ', zipCode: '75002', country: 'France');
    }

    public function test_should_throw_when_country_is_blank(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Country is required.');

        new Address(street: '1 rue de la Paix', city: 'Paris', zipCode: '75002', country: '   ');
    }
}
