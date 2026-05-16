<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class ConversationApiTestCase extends ApiTestCase
{
    protected const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    protected static ?bool $alwaysBootKernel = true;

    protected function insertAccommodation(?Uuid $teamId = null, float $pricePerNight = 100.0): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();
        $entity = new AccommodationEntity()
            ->setId($id)
            ->setTitle('Test')
            ->setDescription('Description')
            ->setPrice($pricePerNight)
            ->setStatus('published')
            ->setTeamId($teamId ?? Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    protected function insertUser(?Uuid $teamId = null, ?string $email = null): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();
        $entity = new UserEntity()
            ->setId($id)
            ->setEmail($email ?? \sprintf('user-%s@example.test', $id->toRfc4122()))
            ->setHashedPassword('$2y$13$dummyhashforfixtures')
            ->setTeamId($teamId ?? Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    protected function requestReservationViaApi(
        string $accommodationId,
        string $guestUserId,
        ?string $note = null,
    ): string {
        $payload = [
            'accommodationId' => $accommodationId,
            'guestUserId' => $guestUserId,
            'checkIn' => '2026-06-01T15:00:00+00:00',
            'checkOut' => '2026-06-05T11:00:00+00:00',
            'guestName' => 'Jean Dupont',
        ];
        if (null !== $note) {
            $payload['note'] = $note;
        }

        $response = self::createClient()->request('POST', '/api/reservations/request', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $payload,
        ]);

        return $response->toArray()['id'];
    }
}
