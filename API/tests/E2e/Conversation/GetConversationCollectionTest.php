<?php

declare(strict_types=1);

namespace App\Tests\E2e\Conversation;

use Symfony\Component\Uid\Uuid;

final class GetConversationCollectionTest extends ConversationApiTestCase
{
    public function test_should_list_conversations_for_team(): void
    {
        $teamId = Uuid::fromString(self::DEFAULT_TEAM_UUID);
        $accommodationId = $this->insertAccommodation($teamId);
        $guestUserId = $this->insertUser(teamId: Uuid::v7());

        $this->requestReservationViaApi($accommodationId, $guestUserId);

        $response = self::createClient()->request('GET', '/api/conversations?teamId='.$teamId->toRfc4122());

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame($guestUserId, $members[0]['guestUserId']);
    }

    public function test_should_return_empty_collection_when_no_filter_provided(): void
    {
        $accommodationId = $this->insertAccommodation();
        $guestUserId = $this->insertUser(teamId: Uuid::v7());
        $this->requestReservationViaApi($accommodationId, $guestUserId);

        $response = self::createClient()->request('GET', '/api/conversations');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }

    public function test_should_return_empty_collection_for_unknown_team(): void
    {
        $response = self::createClient()->request('GET', '/api/conversations?teamId='.Uuid::v7()->toRfc4122());

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
