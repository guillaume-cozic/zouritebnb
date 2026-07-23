<?php

declare(strict_types=1);

namespace App\Tests\Integration\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\AccommodationStatus;
use App\Accommodation\Domain\Entity\Address;
use App\Accommodation\Domain\Entity\Amenities;
use App\Accommodation\Domain\Entity\Capacity;
use App\Accommodation\Domain\Entity\CheckInOut;
use App\Accommodation\Domain\Entity\ExtraServices;
use App\Accommodation\Domain\Entity\Geolocation;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineAccommodationRepositoryTest extends RepositoryTestCase
{
    private AccommodationRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(AccommodationRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Cozy Apartment',
            description: 'A nice place to stay',
            price: 120.50,
        );

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('Cozy Apartment', $found->getTitle());
        self::assertSame('A nice place to stay', $found->getDescription());
        self::assertSame(120.50, $found->getPrice());
        self::assertSame(AccommodationStatus::Draft, $found->getStatus());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Old Title',
            description: 'Old description',
            price: 100.0,
        );
        $this->repository->save($accommodation);

        $updated = new Accommodation(
            id: $id,
            title: 'New Title',
            description: 'New description',
            price: 200.0,
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('New Title', $found->getTitle());
        self::assertSame('New description', $found->getDescription());
        self::assertSame(200.0, $found->getPrice());
    }

    public function test_should_persist_published_status(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Beach House',
            description: 'By the sea',
            price: 350.0,
            status: AccommodationStatus::Published,
        );

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame(AccommodationStatus::Published, $found->getStatus());
    }

    public function test_should_save_and_find_accommodation_with_address(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Mountain Lodge',
            description: 'In the Alps',
            price: 250.0,
        );

        $address = new Address(
            street: '10 Rue de la Paix',
            city: 'Paris',
            zipCode: '75002',
            country: 'France',
        );
        $accommodation->updateAddress($address);
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getAddress());
        self::assertSame('10 Rue de la Paix', $found->getAddress()->street());
        self::assertSame('Paris', $found->getAddress()->city());
        self::assertSame('75002', $found->getAddress()->zipCode());
        self::assertSame('France', $found->getAddress()->country());
    }

    public function test_should_save_and_find_accommodation_with_geolocation(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Geo Lodge',
            description: 'With coordinates',
            price: 180.0,
        );
        $accommodation->updateGeolocation(new Geolocation(latitude: 48.8566, longitude: 2.3522));
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getGeolocation());
        self::assertSame(48.8566, $found->getGeolocation()->latitude());
        self::assertSame(2.3522, $found->getGeolocation()->longitude());
    }

    public function test_should_save_and_find_accommodation_with_capacity(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Family House',
            description: 'Lots of beds',
            price: 300.0,
        );
        $accommodation->updateCapacity(new Capacity(
            bedrooms: 3,
            bathrooms: 2,
            maxGuests: 6,
            singleBeds: 2,
            doubleBeds: 2,
        ));
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getCapacity());
        self::assertSame(3, $found->getCapacity()->bedrooms());
        self::assertSame(2, $found->getCapacity()->bathrooms());
        self::assertSame(6, $found->getCapacity()->maxGuests());
        self::assertSame(2, $found->getCapacity()->singleBeds());
        self::assertSame(2, $found->getCapacity()->doubleBeds());
    }

    public function test_should_save_and_find_accommodation_with_amenities(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Equipped Flat',
            description: 'Has amenities',
            price: 150.0,
        );
        $accommodation->updateAmenities(new Amenities(codes: ['wifi', 'pool', 'parking']));
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getAmenities());
        self::assertSame(['wifi', 'pool', 'parking'], $found->getAmenities()->codes());
    }

    public function test_should_save_and_find_accommodation_with_check_in_out(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Timed Stay',
            description: 'Has check-in times',
            price: 90.0,
        );
        $accommodation->updateCheckInOut(new CheckInOut(checkIn: '15:00', checkOut: '11:00'));
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getCheckInOut());
        self::assertSame('15:00', $found->getCheckInOut()->checkIn());
        self::assertSame('11:00', $found->getCheckInOut()->checkOut());
    }

    public function test_should_save_and_find_accommodation_with_house_rules(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Ruled Stay',
            description: 'Has house rules',
            price: 90.0,
        );
        $accommodation->updateHouseRules(
            smokingAllowed: false,
            petsAllowed: true,
            partiesAllowed: false,
            houseRulesNotes: 'Merci de retirer vos chaussures.',
        );
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertFalse($found->isSmokingAllowed());
        self::assertTrue($found->isPetsAllowed());
        self::assertFalse($found->isPartiesAllowed());
        self::assertSame('Merci de retirer vos chaussures.', $found->getHouseRulesNotes());
    }

    public function test_should_save_and_find_accommodation_with_extra_services(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Serviced Stay',
            description: 'With extra services',
            price: 110.0,
        );
        $accommodation->updateExtraServices(ExtraServices::fromArray([
            ['name' => 'Ménage', 'price' => 30.0],
            ['name' => 'Petit-déjeuner', 'price' => 12.5],
        ]));
        $accommodation->releaseEvents();

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertFalse($found->getExtraServices()->isEmpty());
        self::assertSame([
            ['name' => 'Ménage', 'price' => 30.0],
            ['name' => 'Petit-déjeuner', 'price' => 12.5],
        ], $found->getExtraServices()->toArray());
    }

    public function test_should_update_extra_services_of_existing_accommodation(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Serviced Stay',
            description: 'With extra services',
            price: 110.0,
        );
        $accommodation->updateExtraServices(ExtraServices::fromArray([
            ['name' => 'Ménage', 'price' => 30.0],
        ]));
        $accommodation->releaseEvents();
        $this->repository->save($accommodation);

        $reloaded = $this->repository->findById($id);
        self::assertNotNull($reloaded);
        $reloaded->updateExtraServices(ExtraServices::fromArray([
            ['name' => 'Petit-déjeuner', 'price' => 12.5],
        ]));
        $reloaded->releaseEvents();
        $this->repository->save($reloaded);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame([
            ['name' => 'Petit-déjeuner', 'price' => 12.5],
        ], $found->getExtraServices()->toArray());
    }

    public function test_should_persist_empty_extra_services(): void
    {
        $id = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Bare Stay',
            description: 'No extra services',
            price: 80.0,
        );

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertTrue($found->getExtraServices()->isEmpty());
    }

    public function test_should_save_and_find_fully_populated_accommodation(): void
    {
        $id = Uuid::v4();
        $teamId = Uuid::v4();
        $regionId = Uuid::v4();
        $accommodation = new Accommodation(
            id: $id,
            title: 'Complete Villa',
            description: 'Everything filled in',
            price: 500.0,
            status: AccommodationStatus::Published,
            address: new Address(
                street: '5 Avenue Foch',
                city: 'Nice',
                zipCode: '06000',
                country: 'France',
            ),
            geolocation: new Geolocation(latitude: 43.7102, longitude: 7.2620),
            capacity: new Capacity(
                bedrooms: 4,
                bathrooms: 3,
                maxGuests: 8,
                singleBeds: 1,
                doubleBeds: 3,
            ),
            amenities: new Amenities(codes: ['wifi', 'ac']),
            checkInOut: new CheckInOut(checkIn: '16:00', checkOut: '10:00'),
            teamId: $teamId,
            weeklyPromotionPercentage: 15.0,
            regionId: $regionId,
        );

        $this->repository->save($accommodation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNotNull($found->getAddress());
        self::assertSame('Nice', $found->getAddress()->city());
        self::assertNotNull($found->getGeolocation());
        self::assertSame(43.7102, $found->getGeolocation()->latitude());
        self::assertNotNull($found->getCapacity());
        self::assertSame(4, $found->getCapacity()->bedrooms());
        self::assertNotNull($found->getAmenities());
        self::assertSame(['wifi', 'ac'], $found->getAmenities()->codes());
        self::assertNotNull($found->getCheckInOut());
        self::assertSame('16:00', $found->getCheckInOut()->checkIn());
        self::assertEquals($teamId, $found->getTeamId());
        self::assertEquals($regionId, $found->getRegionId());
        self::assertSame(15.0, $found->getWeeklyPromotionPercentage());
    }
}
