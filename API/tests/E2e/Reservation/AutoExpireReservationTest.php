<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Reservation\Infrastructure\Messenger\ExpireReservationMessage;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class AutoExpireReservationTest extends ReservationApiTestCase
{
    private function insertAccommodation(?Uuid $teamId = null, float $pricePerNight = 100.0): string
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
            ->setTeamId($teamId ?? Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    private function insertUser(?Uuid $teamId = null): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();
        $entity = new UserEntity()
            ->setId($id)
            ->setEmail(\sprintf('u-%s@example.test', $id->toRfc4122()))
            ->setHashedPassword('$2y$13$dummy')
            ->setTeamId($teamId ?? Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    public function test_should_keep_reservation_pending_immediately_after_request(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $response = self::createClient()->request('POST', '/api/reservations/request', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'checkIn' => '2026-06-01T15:00:00+00:00',
                'checkOut' => '2026-06-05T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['status' => 'pending']);
    }

    public function test_should_auto_refuse_and_post_system_message_when_timeout_elapsed(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $client = self::createClient();
        $created = $client->request('POST', '/api/reservations/request', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'checkIn' => '2026-06-01T15:00:00+00:00',
                'checkOut' => '2026-06-05T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);
        $reservationId = $created->toArray()['id'];

        // Simulate the delayed message firing after 24h: re-dispatch with a past dispatchedAt.
        /** @var MessageBusInterface $bus */
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch(new ExpireReservationMessage(
            reservationId: Uuid::fromString($reservationId),
            dispatchedAt: new \DateTimeImmutable('-25 hours'),
        ));

        // Assert reservation is refused.
        $reservation = $client->request('GET', '/api/reservations/'.$reservationId);
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['status' => 'refused']);

        // Assert conversation contains the auto-refuse system message.
        $conversations = $client->request('GET', '/api/conversations?userId='.$guestUserId);
        $messages = $conversations->toArray()['member'][0]['messages'];
        self::assertCount(2, $messages);
        self::assertTrue($messages[1]['isSystem']);
        self::assertStringContainsString('automatiquement refusée', $messages[1]['body']);
        self::assertStringContainsString('24h', $messages[1]['body']);
    }

    public function test_should_not_auto_refuse_when_host_already_confirmed(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $client = self::createClient();
        $created = $client->request('POST', '/api/reservations/request', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'checkIn' => '2026-06-01T15:00:00+00:00',
                'checkOut' => '2026-06-05T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);
        $reservationId = $created->toArray()['id'];

        $client->request('PATCH', '/api/reservations/'.$reservationId.'/confirm', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        /** @var MessageBusInterface $bus */
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch(new ExpireReservationMessage(
            reservationId: Uuid::fromString($reservationId),
            dispatchedAt: new \DateTimeImmutable('-25 hours'),
        ));

        $reservation = $client->request('GET', '/api/reservations/'.$reservationId);
        self::assertJsonContains(['status' => 'confirmed']);
    }

    public function test_manual_refuse_posts_system_message_in_conversation(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $client = self::createClient();
        $created = $client->request('POST', '/api/reservations/request', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'guestUserId' => $guestUserId,
                'checkIn' => '2026-06-01T15:00:00+00:00',
                'checkOut' => '2026-06-05T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);
        $reservationId = $created->toArray()['id'];

        $client->request('PATCH', '/api/reservations/'.$reservationId.'/refuse', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        $conversations = $client->request('GET', '/api/conversations?userId='.$guestUserId);
        $messages = $conversations->toArray()['member'][0]['messages'];
        self::assertCount(2, $messages);
        self::assertTrue($messages[1]['isSystem']);
        self::assertStringContainsString('refusé', $messages[1]['body']);
        self::assertStringNotContainsString('automatiquement', $messages[1]['body']);
    }
}
