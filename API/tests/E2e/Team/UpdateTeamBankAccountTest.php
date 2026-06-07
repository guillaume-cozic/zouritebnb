<?php

declare(strict_types=1);

namespace App\Tests\E2e\Team;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Team\Infrastructure\Doctrine\TeamEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class UpdateTeamBankAccountTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private function insertTeam(): string
    {
        $id = Uuid::v7();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new TeamEntity()->setId($id);

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    public function test_should_update_bank_account_and_return204(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890185',
                'bic' => 'BDFEFRPPCCT',
                'holderName' => 'Marie Hôte',
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', \sprintf('/api/teams/%s', $teamId), [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'iban' => 'FR7630001007941234567890185',
            'bic' => 'BDFEFRPPCCT',
            'bankAccountHolderName' => 'Marie Hôte',
        ]);
    }

    public function test_should_update_bank_account_without_bic_and_return204(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890185',
                'holderName' => 'Marie Hôte',
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', \sprintf('/api/teams/%s', $teamId), [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'iban' => 'FR7630001007941234567890185',
            'bankAccountHolderName' => 'Marie Hôte',
        ]);
        // null values are omitted from the serialized response (skip_null_values)
        self::assertArrayNotHasKey('bic', $response->toArray());
    }

    public function test_should_clear_bank_account_when_iban_is_null_and_return204(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890185',
                'holderName' => 'Marie Hôte',
            ],
        ]);
        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['iban' => null],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', \sprintf('/api/teams/%s', $teamId), [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        // null values are omitted from the serialized response (skip_null_values),
        // so once the bank account is cleared these keys must be absent
        $data = $response->toArray();
        self::assertArrayNotHasKey('iban', $data);
        self::assertArrayNotHasKey('bic', $data);
        self::assertArrayNotHasKey('bankAccountHolderName', $data);
    }

    public function test_should_return404_when_team_does_not_exist(): void
    {
        $teamId = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890185',
                'holderName' => 'Marie Hôte',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return422_when_iban_format_is_invalid(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'NOT-AN-IBAN',
                'holderName' => 'Marie Hôte',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_iban_checksum_is_invalid(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890186',
                'holderName' => 'Marie Hôte',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_bic_format_is_invalid(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890185',
                'bic' => 'INVALID',
                'holderName' => 'Marie Hôte',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_holder_name_is_missing(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890185',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_holder_name_is_empty(): void
    {
        $teamId = $this->insertTeam();

        self::createClient()->request('PATCH', \sprintf('/api/teams/%s/bank-account', $teamId), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'iban' => 'FR7630001007941234567890185',
                'holderName' => '   ',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
