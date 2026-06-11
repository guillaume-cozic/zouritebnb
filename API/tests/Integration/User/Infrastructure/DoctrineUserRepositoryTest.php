<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure;

use App\Tests\Integration\RepositoryTestCase;
use App\User\Domain\Entity\IdentityDocument;
use App\User\Domain\Entity\IdentityDocumentType;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\VerificationStatus;
use App\User\Domain\Port\UserRepository;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineUserRepositoryTest extends RepositoryTestCase
{
    private UserRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(UserRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $teamId = Uuid::v4();
        $user = new User(
            id: $id,
            email: 'john@example.com',
            hashedPassword: 'hashed-password',
            teamId: $teamId,
            firstName: 'John',
            lastName: 'Doe',
        );

        $this->repository->save($user);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('john@example.com', $found->getEmail());
        self::assertSame('hashed-password', $found->getHashedPassword());
        self::assertEquals($teamId, $found->getTeamId());
        self::assertSame('John', $found->getFirstName());
        self::assertSame('Doe', $found->getLastName());
    }

    public function test_should_return_null_when_not_found_by_id(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_save_and_find_by_email(): void
    {
        $id = Uuid::v4();
        $teamId = Uuid::v4();
        $user = new User(
            id: $id,
            email: 'jane@example.com',
            hashedPassword: 'secret',
            teamId: $teamId,
            firstName: 'Jane',
            lastName: 'Roe',
        );

        $this->repository->save($user);
        $found = $this->repository->findByEmail('jane@example.com');

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('jane@example.com', $found->getEmail());
        self::assertEquals($teamId, $found->getTeamId());
    }

    public function test_should_return_null_when_not_found_by_email(): void
    {
        $result = $this->repository->findByEmail('missing@example.com');

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $teamId = Uuid::v4();
        $user = new User(
            id: $id,
            email: 'old@example.com',
            hashedPassword: 'old-hash',
            teamId: $teamId,
            firstName: 'Old',
            lastName: 'Name',
        );
        $this->repository->save($user);

        $updated = new User(
            id: $id,
            email: 'new@example.com',
            hashedPassword: 'new-hash',
            teamId: $teamId,
            firstName: 'New',
            lastName: 'Surname',
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('new@example.com', $found->getEmail());
        self::assertSame('new-hash', $found->getHashedPassword());
        self::assertSame('New', $found->getFirstName());
        self::assertSame('Surname', $found->getLastName());
    }

    public function test_should_save_and_find_user_with_null_names(): void
    {
        $id = Uuid::v4();
        $teamId = Uuid::v4();
        $user = new User(
            id: $id,
            email: 'noname@example.com',
            hashedPassword: 'hash',
            teamId: $teamId,
        );

        $this->repository->save($user);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNull($found->getFirstName());
        self::assertNull($found->getLastName());
    }

    public function test_should_default_to_not_started_verification_status(): void
    {
        $id = Uuid::v4();
        $user = new User(
            id: $id,
            email: 'fresh@example.com',
            hashedPassword: 'hash',
            teamId: Uuid::v4(),
        );

        $this->repository->save($user);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame(VerificationStatus::NotStarted, $found->getVerificationStatus());
        self::assertNull($found->getVerifiedAt());
    }

    public function test_should_persist_and_read_back_verification_state(): void
    {
        $id = Uuid::v4();
        $documentId = Uuid::v4();
        $selfieId = Uuid::v4();
        $verifiedAt = new \DateTimeImmutable('2026-06-07T12:00:00+00:00');

        $user = new User(
            id: $id,
            email: 'verified@example.com',
            hashedPassword: 'hash',
            teamId: Uuid::v4(),
        );
        $user->submitAndVerifyIdentity(
            documentId: $documentId,
            selfieId: $selfieId,
            documentType: IdentityDocumentType::DrivingLicense,
            document: new IdentityDocument('doc', 'doc.jpg', 'image/jpeg', 1),
            selfie: new IdentityDocument('selfie', 'selfie.jpg', 'image/jpeg', 1),
            verifiedAt: $verifiedAt,
        );

        $this->repository->save($user);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame(VerificationStatus::Verified, $found->getVerificationStatus());
        self::assertSame(IdentityDocumentType::DrivingLicense, $found->getDocumentType());
        self::assertEquals($documentId, $found->getIdentityDocumentId());
        self::assertEquals($selfieId, $found->getSelfieId());
        self::assertEquals($verifiedAt, $found->getVerifiedAt());
    }
}
