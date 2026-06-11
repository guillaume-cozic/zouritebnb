<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\IdentityDocument;
use App\User\Domain\Entity\IdentityDocumentType;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\VerificationStatus;
use App\User\Domain\Event\IdentityVerified;
use App\User\Domain\Event\UserRegistered;
use App\User\Domain\Exception\IdentityVerificationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserTest extends TestCase
{
    public function test_should_create_a_user_with_all_fields(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');

        $user = new User(
            id: $id,
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: $teamId,
            firstName: 'John',
            lastName: 'Doe',
        );

        self::assertSame($id, $user->getId());
        self::assertSame('john@example.com', $user->getEmail());
        self::assertSame('hashed-secret', $user->getHashedPassword());
        self::assertSame($teamId, $user->getTeamId());
        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
    }

    public function test_should_create_a_user_without_names(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'jane@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());
    }

    public function test_should_not_record_any_event_on_plain_construction(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'jane@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        self::assertSame([], $user->releaseEvents());
    }

    public function test_should_register_a_user_and_record_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');

        $user = User::register(
            id: $id,
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: $teamId,
        );

        self::assertSame($id, $user->getId());
        self::assertSame('john@example.com', $user->getEmail());
        self::assertSame('hashed-secret', $user->getHashedPassword());
        self::assertSame($teamId, $user->getTeamId());
        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());

        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
        self::assertTrue($id->equals($events[0]->userId));
        self::assertTrue($teamId->equals($events[0]->teamId));
    }

    public function test_should_release_events_only_once(): void
    {
        $user = User::register(
            id: Uuid::v7(),
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        self::assertCount(1, $user->releaseEvents());
        self::assertSame([], $user->releaseEvents());
    }

    public function test_should_update_profile(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'old@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
            firstName: 'Old',
            lastName: 'Name',
        );

        $user->updateProfile(firstName: 'New', lastName: 'Surname', email: 'new@example.com');

        self::assertSame('New', $user->getFirstName());
        self::assertSame('Surname', $user->getLastName());
        self::assertSame('new@example.com', $user->getEmail());
    }

    public function test_should_update_profile_with_null_names(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'old@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
            firstName: 'Old',
            lastName: 'Name',
        );

        $user->updateProfile(firstName: null, lastName: null, email: 'new@example.com');

        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());
        self::assertSame('new@example.com', $user->getEmail());
    }

    public function test_should_not_change_hashed_password_when_updating_profile(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'old@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        $user->updateProfile(firstName: 'A', lastName: 'B', email: 'new@example.com');

        self::assertSame('hashed-secret', $user->getHashedPassword());
    }

    public function test_should_be_not_started_by_default(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        self::assertSame(VerificationStatus::NotStarted, $user->getVerificationStatus());
        self::assertNull($user->getDocumentType());
        self::assertNull($user->getIdentityDocumentId());
        self::assertNull($user->getSelfieId());
        self::assertNull($user->getVerifiedAt());
    }

    public function test_should_submit_and_verify_identity_recording_a_single_event(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        $documentId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000aa');
        $selfieId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000bb');
        $verifiedAt = new \DateTimeImmutable('2026-06-07T12:00:00+00:00');

        $user->submitAndVerifyIdentity(
            documentId: $documentId,
            selfieId: $selfieId,
            documentType: IdentityDocumentType::Passport,
            document: new IdentityDocument('doc-bytes', 'passport.png', 'image/png', 10),
            selfie: new IdentityDocument('selfie-bytes', 'selfie.jpg', 'image/jpeg', 20),
            verifiedAt: $verifiedAt,
        );

        self::assertSame(VerificationStatus::Verified, $user->getVerificationStatus());
        self::assertSame(IdentityDocumentType::Passport, $user->getDocumentType());
        self::assertSame($documentId, $user->getIdentityDocumentId());
        self::assertSame($selfieId, $user->getSelfieId());
        self::assertSame($verifiedAt, $user->getVerifiedAt());

        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(IdentityVerified::class, $events[0]);
        self::assertTrue($user->getId()->equals($events[0]->userId));
        self::assertSame($documentId->toRfc4122().'.png', $events[0]->documentFilename);
        self::assertSame('doc-bytes', $events[0]->documentContent);
        self::assertSame($selfieId->toRfc4122().'.jpg', $events[0]->selfieFilename);
        self::assertSame('selfie-bytes', $events[0]->selfieContent);
    }

    public function test_should_reject_resubmission_when_already_verified(): void
    {
        $user = new User(
            id: Uuid::v7(),
            email: 'john@example.com',
            hashedPassword: 'hashed-secret',
            teamId: Uuid::v7(),
        );

        $submit = static fn () => $user->submitAndVerifyIdentity(
            documentId: Uuid::v7(),
            selfieId: Uuid::v7(),
            documentType: IdentityDocumentType::IdCard,
            document: new IdentityDocument('doc', 'doc.jpg', 'image/jpeg', 1),
            selfie: new IdentityDocument('selfie', 'selfie.jpg', 'image/jpeg', 1),
            verifiedAt: new \DateTimeImmutable(),
        );

        $submit();
        $user->releaseEvents();

        $this->expectException(IdentityVerificationException::class);
        $submit();
    }
}
