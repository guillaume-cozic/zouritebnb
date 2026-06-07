<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Conversation\Infrastructure\Doctrine\ConversationEntity;
use App\Conversation\Infrastructure\Doctrine\MessageEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class ConversationApiTestCase extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    protected static ?bool $alwaysBootKernel = true;

    /**
     * Seeds a conversation (with its system opening message) directly through Doctrine
     * so the tests do not depend on the Reservation module's authentication state.
     *
     * @return string the conversation UUID (RFC4122)
     */
    protected function seedConversation(
        string $guestUserId,
        ?string $teamId = null,
        ?string $accommodationId = null,
        ?string $reservationId = null,
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $conversation = new ConversationEntity()
            ->setId(Uuid::v7())
            ->setReservationId(Uuid::fromString($reservationId ?? Uuid::v7()->toRfc4122()))
            ->setAccommodationId(Uuid::fromString($accommodationId ?? Uuid::v7()->toRfc4122()))
            ->setTeamId(Uuid::fromString($teamId ?? self::DEFAULT_TEAM_UUID))
            ->setGuestUserId(Uuid::fromString($guestUserId))
            ->setCreatedAt(new \DateTimeImmutable('2026-05-14T09:00:00+00:00'));

        $openingMessage = new MessageEntity()
            ->setId(Uuid::v7())
            ->setAuthorUserId(null)
            ->setBody('Jean Dupont a demandé une réservation.')
            ->setSentAt(new \DateTimeImmutable('2026-05-14T09:00:00+00:00'))
            ->setIsSystem(true);

        $conversation->addMessage($openingMessage);

        $em->persist($conversation);
        $em->persist($openingMessage);
        $em->flush();

        return $conversation->getId()->toRfc4122();
    }
}
