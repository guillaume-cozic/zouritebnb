<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\AccommodationStatus;
use App\Accommodation\Domain\Entity\Address;
use App\Accommodation\Domain\Entity\Amenities;
use App\Accommodation\Domain\Entity\Capacity;
use App\Accommodation\Domain\Entity\CheckInOut;
use App\Accommodation\Domain\Entity\Geolocation;
use App\Accommodation\Domain\Event\AccommodationAddressUpdated;
use App\Accommodation\Domain\Event\AccommodationAmenitiesUpdated;
use App\Accommodation\Domain\Event\AccommodationCapacityUpdated;
use App\Accommodation\Domain\Event\AccommodationCheckInOutUpdated;
use App\Accommodation\Domain\Event\AccommodationDescriptionUpdated;
use App\Accommodation\Domain\Event\AccommodationGeolocationUpdated;
use App\Accommodation\Domain\Event\AccommodationPriceUpdated;
use App\Accommodation\Domain\Event\AccommodationPublished;
use App\Accommodation\Domain\Event\AccommodationUnpublished;
use App\Accommodation\Domain\Event\AccommodationWeeklyPromotionUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotPublishableException;
use App\Accommodation\Domain\Exception\InvalidPriceException;
use App\Accommodation\Domain\Exception\InvalidWeeklyPromotionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AccommodationTest extends TestCase
{
    private const ID = '01961e2f-dead-7000-beef-000000000001';

    public function test_should_create_a_valid_accommodation_with_defaults(): void
    {
        $id = Uuid::fromString(self::ID);
        $accommodation = new Accommodation(
            id: $id,
            title: 'Cozy loft',
            description: 'A nice loft',
            price: 120.0,
        );

        self::assertTrue($id->equals($accommodation->getId()));
        self::assertSame('Cozy loft', $accommodation->getTitle());
        self::assertSame('A nice loft', $accommodation->getDescription());
        self::assertSame(120.0, $accommodation->getPrice());
        self::assertSame(AccommodationStatus::Draft, $accommodation->getStatus());
        self::assertNull($accommodation->getAddress());
        self::assertNull($accommodation->getGeolocation());
        self::assertNull($accommodation->getCapacity());
        self::assertNull($accommodation->getAmenities());
        self::assertNull($accommodation->getCheckInOut());
        self::assertNull($accommodation->getTeamId());
        self::assertNull($accommodation->getWeeklyPromotionPercentage());
        self::assertNull($accommodation->getRegionId());
        self::assertSame([], $accommodation->releaseEvents());
    }

    public function test_should_create_accommodation_with_all_optional_fields(): void
    {
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');
        $regionId = Uuid::fromString('01961e2f-dead-7000-beef-000000000003');
        $address = new Address('1 rue', 'Paris', '75000', 'France');
        $geolocation = new Geolocation(48.0, 2.0);
        $capacity = new Capacity(1, 1, 2, 1, 0);
        $amenities = new Amenities(['wifi']);
        $checkInOut = new CheckInOut('15:00', '11:00');

        $accommodation = new Accommodation(
            id: Uuid::fromString(self::ID),
            title: 'Title',
            description: 'Desc',
            price: 99.0,
            status: AccommodationStatus::Published,
            address: $address,
            geolocation: $geolocation,
            capacity: $capacity,
            amenities: $amenities,
            checkInOut: $checkInOut,
            teamId: $teamId,
            weeklyPromotionPercentage: 10.0,
            regionId: $regionId,
        );

        self::assertSame(AccommodationStatus::Published, $accommodation->getStatus());
        self::assertSame($address, $accommodation->getAddress());
        self::assertSame($geolocation, $accommodation->getGeolocation());
        self::assertSame($capacity, $accommodation->getCapacity());
        self::assertSame($amenities, $accommodation->getAmenities());
        self::assertSame($checkInOut, $accommodation->getCheckInOut());
        self::assertTrue($teamId->equals($accommodation->getTeamId()));
        self::assertSame(10.0, $accommodation->getWeeklyPromotionPercentage());
        self::assertTrue($regionId->equals($accommodation->getRegionId()));
    }

    #[DataProvider('invalidPriceProvider')]
    public function test_should_throw_when_price_is_not_strictly_positive(float $price): void
    {
        $this->expectException(InvalidPriceException::class);
        $this->expectExceptionMessage(\sprintf('Price must be strictly positive, got %s.', $price));

        $this->createAccommodation(price: $price);
    }

    public static function invalidPriceProvider(): \Generator
    {
        yield 'zero' => [0.0];
        yield 'negative' => [-5.0];
    }

    public function test_should_update_description(): void
    {
        $accommodation = $this->createAccommodation();

        $accommodation->updateDescription('New title', 'New description');

        self::assertSame('New title', $accommodation->getTitle());
        self::assertSame('New description', $accommodation->getDescription());
        $this->assertSingleEvent($accommodation, AccommodationDescriptionUpdated::class);
    }

    public function test_should_publish(): void
    {
        $accommodation = $this->createAccommodation();

        $accommodation->publish(Accommodation::MIN_PHOTOS_TO_PUBLISH);

        self::assertSame(AccommodationStatus::Published, $accommodation->getStatus());
        $this->assertSingleEvent($accommodation, AccommodationPublished::class);
    }

    public function test_should_not_publish_with_too_few_photos(): void
    {
        $accommodation = $this->createAccommodation();

        $this->expectException(AccommodationNotPublishableException::class);

        $accommodation->publish(Accommodation::MIN_PHOTOS_TO_PUBLISH - 1);
    }

    #[DataProvider('incompleteAccommodationProvider')]
    public function test_should_not_publish_when_a_requirement_is_missing(string $title, string $description): void
    {
        $accommodation = new Accommodation(
            id: Uuid::fromString(self::ID),
            title: $title,
            description: $description,
            price: 100.0,
        );

        $this->expectException(AccommodationNotPublishableException::class);

        $accommodation->publish(Accommodation::MIN_PHOTOS_TO_PUBLISH);
    }

    /** @return \Generator<string, array{string, string}> */
    public static function incompleteAccommodationProvider(): \Generator
    {
        yield 'blank title' => ['   ', 'Desc'];
        yield 'empty description' => ['Title', ''];
    }

    public function test_should_unpublish(): void
    {
        $accommodation = $this->createAccommodation(status: AccommodationStatus::Published);

        $accommodation->unpublish();

        self::assertSame(AccommodationStatus::Draft, $accommodation->getStatus());
        $this->assertSingleEvent($accommodation, AccommodationUnpublished::class);
    }

    public function test_should_update_price(): void
    {
        $accommodation = $this->createAccommodation();

        $accommodation->updatePrice(200.0);

        self::assertSame(200.0, $accommodation->getPrice());
        $this->assertSingleEvent($accommodation, AccommodationPriceUpdated::class);
    }

    #[DataProvider('invalidPriceProvider')]
    public function test_should_throw_when_updating_with_invalid_price(float $price): void
    {
        $accommodation = $this->createAccommodation();

        $this->expectException(InvalidPriceException::class);
        $this->expectExceptionMessage(\sprintf('Price must be strictly positive, got %s.', $price));

        $accommodation->updatePrice($price);
    }

    public function test_should_update_address(): void
    {
        $accommodation = $this->createAccommodation();
        $address = new Address('2 rue', 'Lyon', '69000', 'France');

        $accommodation->updateAddress($address);

        self::assertSame($address, $accommodation->getAddress());
        $this->assertSingleEvent($accommodation, AccommodationAddressUpdated::class);
    }

    public function test_should_update_geolocation(): void
    {
        $accommodation = $this->createAccommodation();
        $geolocation = new Geolocation(45.0, 4.0);

        $accommodation->updateGeolocation($geolocation);

        self::assertSame($geolocation, $accommodation->getGeolocation());
        $this->assertSingleEvent($accommodation, AccommodationGeolocationUpdated::class);
    }

    public function test_should_update_capacity(): void
    {
        $accommodation = $this->createAccommodation();
        $capacity = new Capacity(2, 1, 4, 2, 1);

        $accommodation->updateCapacity($capacity);

        self::assertSame($capacity, $accommodation->getCapacity());
        $this->assertSingleEvent($accommodation, AccommodationCapacityUpdated::class);
    }

    public function test_should_update_amenities(): void
    {
        $accommodation = $this->createAccommodation();
        $amenities = new Amenities(['pool', 'gym']);

        $accommodation->updateAmenities($amenities);

        self::assertSame($amenities, $accommodation->getAmenities());
        $this->assertSingleEvent($accommodation, AccommodationAmenitiesUpdated::class);
    }

    public function test_should_update_check_in_out(): void
    {
        $accommodation = $this->createAccommodation();
        $checkInOut = new CheckInOut('16:00', '10:00');

        $accommodation->updateCheckInOut($checkInOut);

        self::assertSame($checkInOut, $accommodation->getCheckInOut());
        $this->assertSingleEvent($accommodation, AccommodationCheckInOutUpdated::class);
    }

    public function test_should_update_weekly_promotion(): void
    {
        $accommodation = $this->createAccommodation();

        $accommodation->updateWeeklyPromotion(25.0);

        self::assertSame(25.0, $accommodation->getWeeklyPromotionPercentage());
        $event = $this->assertSingleEvent($accommodation, AccommodationWeeklyPromotionUpdated::class);
        self::assertInstanceOf(AccommodationWeeklyPromotionUpdated::class, $event);
        self::assertSame(25.0, $event->weeklyPromotionPercentage);
    }

    public function test_should_clear_weekly_promotion_when_null(): void
    {
        $accommodation = $this->createAccommodation(weeklyPromotionPercentage: 30.0);

        $accommodation->updateWeeklyPromotion(null);

        self::assertNull($accommodation->getWeeklyPromotionPercentage());
        $event = $this->assertSingleEvent($accommodation, AccommodationWeeklyPromotionUpdated::class);
        self::assertInstanceOf(AccommodationWeeklyPromotionUpdated::class, $event);
        self::assertNull($event->weeklyPromotionPercentage);
    }

    #[DataProvider('invalidPromotionProvider')]
    public function test_should_throw_when_weekly_promotion_is_out_of_bounds(float $percentage): void
    {
        $accommodation = $this->createAccommodation();

        $this->expectException(InvalidWeeklyPromotionException::class);
        $this->expectExceptionMessage(\sprintf(
            'Weekly promotion percentage must be strictly greater than 0 and less than or equal to 100, got %s.',
            $percentage,
        ));

        $accommodation->updateWeeklyPromotion($percentage);
    }

    public static function invalidPromotionProvider(): \Generator
    {
        yield 'zero' => [0.0];
        yield 'negative' => [-1.0];
        yield 'above 100' => [101.0];
    }

    private function createAccommodation(
        float $price = 100.0,
        AccommodationStatus $status = AccommodationStatus::Draft,
        ?float $weeklyPromotionPercentage = null,
    ): Accommodation {
        return new Accommodation(
            id: Uuid::fromString(self::ID),
            title: 'Title',
            description: 'Desc',
            price: $price,
            status: $status,
            weeklyPromotionPercentage: $weeklyPromotionPercentage,
        );
    }

    private function assertSingleEvent(Accommodation $accommodation, string $eventClass): object
    {
        $events = $accommodation->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf($eventClass, $events[0]);

        return $events[0];
    }
}
