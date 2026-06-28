<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class ReservationModificationTest extends ReservationApiTestCase
{
    private function insertAccommodation(float $pricePerNight = 100.0): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();
        $entity = new AccommodationEntity()
            ->setId($id)
            ->setTitle('Test')
            ->setDescription('Test description')
            ->setPrice($pricePerNight)
            ->setStatus('published')
            ->setTeamId(Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    private function newDates(): array
    {
        return [
            'checkIn' => (new \DateTimeImmutable('+40 days'))->format(\DateTimeInterface::ATOM),
            'checkOut' => (new \DateTimeImmutable('+44 days'))->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array{0: string, 1: string} guest email + reservation id (confirmed, future, owned by default host team)
     */
    private function givenConfirmedReservationWithGuest(string $guestEmail = 'guest@example.com'): array
    {
        $guestUserId = $this->createAuthUser(email: $guestEmail, teamId: Uuid::v7()->toRfc4122());
        $id = $this->insertReservation(
            accommodationId: $this->insertAccommodation(),
            guestUserId: $guestUserId,
            checkIn: (new \DateTimeImmutable('+20 days'))->format(\DateTimeInterface::ATOM),
            checkOut: (new \DateTimeImmutable('+24 days'))->format(\DateTimeInterface::ATOM),
            status: 'confirmed',
        );

        return [$guestEmail, $id];
    }

    public function test_guest_can_request_a_date_modification(): void
    {
        [$guestEmail, $id] = $this->givenConfirmedReservationWithGuest();

        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification-request', [
            'headers' => $this->authHeaders($guestEmail) + ['Content-Type' => 'application/ld+json'],
            'json' => $this->newDates(),
        ]);

        self::assertResponseIsSuccessful();
        // The reservation keeps its original confirmed status; the change waits for the host.
        self::assertJsonContains(['status' => 'confirmed']);
    }

    public function test_host_can_approve_a_modification(): void
    {
        [$guestEmail, $id] = $this->givenConfirmedReservationWithGuest();
        $dates = $this->newDates();

        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification-request', [
            'headers' => $this->authHeaders($guestEmail) + ['Content-Type' => 'application/ld+json'],
            'json' => $dates,
        ]);
        self::assertResponseIsSuccessful();

        $hostHeaders = $this->hostAuthHeaders();
        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification/approve', [
            'headers' => $hostHeaders + ['Content-Type' => 'application/ld+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        $approved = self::createClient()->request('GET', '/api/reservations/'.$id, ['headers' => $hostHeaders])->toArray();
        self::assertSame($dates['checkIn'], $approved['checkIn']);
        self::assertNull($approved['pendingModification']);
    }

    public function test_host_can_reject_a_modification(): void
    {
        [$guestEmail, $id] = $this->givenConfirmedReservationWithGuest();

        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification-request', [
            'headers' => $this->authHeaders($guestEmail) + ['Content-Type' => 'application/ld+json'],
            'json' => $this->newDates(),
        ]);
        self::assertResponseIsSuccessful();

        $hostHeaders = $this->hostAuthHeaders();
        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification/reject', [
            'headers' => $hostHeaders + ['Content-Type' => 'application/ld+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        $rejected = self::createClient()->request('GET', '/api/reservations/'.$id, ['headers' => $hostHeaders])->toArray();
        self::assertNull($rejected['pendingModification']);
        self::assertSame('confirmed', $rejected['status']);
    }

    public function test_host_cannot_request_a_modification(): void
    {
        [, $id] = $this->givenConfirmedReservationWithGuest();

        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification-request', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/ld+json'],
            'json' => $this->newDates(),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_guest_cannot_approve_a_modification(): void
    {
        [$guestEmail, $id] = $this->givenConfirmedReservationWithGuest();

        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification-request', [
            'headers' => $this->authHeaders($guestEmail) + ['Content-Type' => 'application/ld+json'],
            'json' => $this->newDates(),
        ]);
        self::assertResponseIsSuccessful();

        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification/approve', [
            'headers' => $this->authHeaders($guestEmail) + ['Content-Type' => 'application/ld+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_cannot_modify_a_pending_reservation(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest2@example.com', teamId: Uuid::v7()->toRfc4122());
        $id = $this->insertReservation(
            accommodationId: $this->insertAccommodation(),
            guestUserId: $guestUserId,
            checkIn: (new \DateTimeImmutable('+20 days'))->format(\DateTimeInterface::ATOM),
            checkOut: (new \DateTimeImmutable('+24 days'))->format(\DateTimeInterface::ATOM),
            status: 'pending',
        );

        self::createClient()->request('POST', '/api/reservations/'.$id.'/modification-request', [
            'headers' => $this->authHeaders('guest2@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => $this->newDates(),
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
