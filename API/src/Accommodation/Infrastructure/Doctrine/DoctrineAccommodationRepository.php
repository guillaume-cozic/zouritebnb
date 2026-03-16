<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use App\Accommodation\Domain\Entity\Accommodation as DomainAccommodation;
use App\Accommodation\Domain\Entity\Address;
use App\Accommodation\Domain\Entity\Amenities;
use App\Accommodation\Domain\Entity\Capacity;
use App\Accommodation\Domain\Entity\CheckInOut;
use App\Accommodation\Domain\Entity\Geolocation;
use App\Accommodation\Domain\Port\AccommodationRepository as AccommodationRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AccommodationEntity>
 */
class DoctrineAccommodationRepository extends ServiceEntityRepository implements AccommodationRepositoryPort
{
    public function __construct(ManagerRegistry $registry, private readonly SerializerInterface $serializer)
    {
        parent::__construct($registry, AccommodationEntity::class);
    }

    public function findById(Uuid $id): ?DomainAccommodation
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function save(DomainAccommodation $accommodation): void
    {
        $entity = $this->find($accommodation->getId()) ?? new AccommodationEntity();
        $data = $this->serializer->normalize($accommodation);

        $addressData = $data['address'] ?? null;
        $geolocationData = $data['geolocation'] ?? null;
        $capacityData = $data['capacity'] ?? null;
        $amenitiesData = $data['amenities'] ?? null;
        $checkInOutData = $data['checkInOut'] ?? null;
        unset($data['address'], $data['geolocation'], $data['capacity'], $data['amenities'], $data['checkInOut']);

        if (\is_array($checkInOutData)) {
            $data['checkIn'] = $checkInOutData['checkIn'] ?? null;
            $data['checkOut'] = $checkInOutData['checkOut'] ?? null;
        }

        if (\is_array($addressData)) {
            $data = array_merge($data, $addressData);
        }

        if (\is_array($geolocationData)) {
            $data = array_merge($data, $geolocationData);
        }

        if (\is_array($capacityData)) {
            $data = array_merge($data, $capacityData);
        }

        if (\is_array($amenitiesData)) {
            $data['amenities'] = $amenitiesData['codes'] ?? $amenitiesData;
        }

        $this->serializer->denormalize($data, AccommodationEntity::class, null, [
            AbstractNormalizer::OBJECT_TO_POPULATE => $entity,
        ]);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    private function toDomain(AccommodationEntity $entity): DomainAccommodation
    {
        $data = $this->serializer->normalize($entity);

        if (null !== $data['street']) {
            $data['address'] = [
                'street' => $data['street'],
                'city' => $data['city'],
                'zipCode' => $data['zipCode'],
                'country' => $data['country'],
            ];
        }

        if (null !== $data['latitude']) {
            $data['geolocation'] = [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ];
        }

        if (null !== $data['bedrooms']) {
            $data['capacity'] = [
                'bedrooms' => $data['bedrooms'],
                'bathrooms' => $data['bathrooms'],
                'maxGuests' => $data['maxGuests'],
                'singleBeds' => $data['singleBeds'],
                'doubleBeds' => $data['doubleBeds'],
            ];
        }

        $amenitiesArray = $data['amenities'] ?? null;
        $checkIn = $data['checkIn'] ?? null;
        $checkOut = $data['checkOut'] ?? null;
        unset($data['street'], $data['city'], $data['zipCode'], $data['country'], $data['latitude'], $data['longitude'], $data['bedrooms'], $data['bathrooms'], $data['maxGuests'], $data['singleBeds'], $data['doubleBeds'], $data['amenities'], $data['checkIn'], $data['checkOut']);

        $accommodation = $this->serializer->denormalize($data, DomainAccommodation::class);

        if (isset($data['address'])) {
            $address = new Address(
                street: $data['address']['street'],
                city: $data['address']['city'],
                zipCode: $data['address']['zipCode'],
                country: $data['address']['country'],
            );
            $accommodation->updateAddress($address);
        }

        if (isset($data['geolocation'])) {
            $geolocation = new Geolocation(
                latitude: $data['geolocation']['latitude'],
                longitude: $data['geolocation']['longitude'],
            );
            $accommodation->updateGeolocation($geolocation);
        }

        if (isset($data['capacity'])) {
            $capacity = new Capacity(
                bedrooms: $data['capacity']['bedrooms'],
                bathrooms: $data['capacity']['bathrooms'],
                maxGuests: $data['capacity']['maxGuests'],
                singleBeds: $data['capacity']['singleBeds'],
                doubleBeds: $data['capacity']['doubleBeds'],
            );
            $accommodation->updateCapacity($capacity);
        }

        if (null !== $amenitiesArray) {
            $amenities = new Amenities(codes: $amenitiesArray);
            $accommodation->updateAmenities($amenities);
        }

        if (null !== $checkIn && null !== $checkOut) {
            $checkInOut = new CheckInOut(checkIn: $checkIn, checkOut: $checkOut);
            $accommodation->updateCheckInOut($checkInOut);
        }

        $accommodation->releaseEvents();

        return $accommodation;
    }
}
