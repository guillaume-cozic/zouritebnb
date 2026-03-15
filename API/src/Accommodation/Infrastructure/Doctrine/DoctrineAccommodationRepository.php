<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use App\Accommodation\Domain\Entity\Accommodation as DomainAccommodation;
use App\Accommodation\Domain\Entity\Address;
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
        unset($data['address'], $data['geolocation']);

        if (\is_array($addressData)) {
            $data = array_merge($data, $addressData);
        }

        if (\is_array($geolocationData)) {
            $data = array_merge($data, $geolocationData);
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

        unset($data['street'], $data['city'], $data['zipCode'], $data['country'], $data['latitude'], $data['longitude']);

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

        $accommodation->releaseEvents();

        return $accommodation;
    }
}
