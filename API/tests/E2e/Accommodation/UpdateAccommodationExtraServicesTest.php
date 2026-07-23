<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use PHPUnit\Framework\Attributes\DataProvider;

final class UpdateAccommodationExtraServicesTest extends AccommodationApiTestCase
{
    public function test_should_set_extra_services(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => [
                ['name' => 'Ménage', 'price' => 30.0, 'billedWithReservation' => true],
                ['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false],
            ]],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'extraServices' => [
                ['name' => 'Ménage', 'price' => 30, 'billedWithReservation' => true],
                ['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false],
            ],
        ]);
    }

    public function test_should_default_billed_with_reservation_to_false_when_key_absent(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => [
                ['name' => 'Petit-déjeuner', 'price' => 12.5],
            ]],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertSame([
            ['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false],
        ], $response->toArray()['extraServices']);
    }

    public function test_should_replace_extra_services(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => [
                ['name' => 'Ménage', 'price' => 30.0],
            ]],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => [
                ['name' => 'Petit-déjeuner', 'price' => 12.5],
            ]],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertSame([
            ['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false],
        ], $response->toArray()['extraServices']);
    }

    public function test_should_clear_extra_services(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => [
                ['name' => 'Ménage', 'price' => 30.0],
            ]],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => []],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['extraServices']);
    }

    /**
     * @param array<mixed> $extraServices
     */
    #[DataProvider('provideInvalidExtraServices')]
    public function test_should_reject_invalid_extra_services(array $extraServices): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => $extraServices],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return \Generator<string, array{array<mixed>}>
     */
    public static function provideInvalidExtraServices(): \Generator
    {
        yield 'empty name' => [[['name' => '', 'price' => 30.0]]];
        yield 'name too long' => [[['name' => str_repeat('a', 101), 'price' => 30.0]]];
        yield 'zero price' => [[['name' => 'Ménage', 'price' => 0.0]]];
        yield 'negative price' => [[['name' => 'Ménage', 'price' => -30.0]]];
        yield 'malformed item' => [[['label' => 'Ménage']]];
        yield 'non-boolean billedWithReservation' => [[['name' => 'Ménage', 'price' => 30.0, 'billedWithReservation' => 'yes']]];
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => []],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => []],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_404_when_accommodation_not_found(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('PUT', '/api/accommodations/00000000-0000-0000-0000-000000000000/extra-services', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['extraServices' => []],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
