<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\Event\UserRegistered;
use App\User\Domain\Event\IdentityVerified;
use App\User\Domain\Exception\IdentityVerificationException;
use Symfony\Component\Uid\Uuid;

final class User extends AggregateRoot
{
    public function __construct(
        private readonly Uuid $id,
        private string $email,
        private readonly string $hashedPassword,
        private readonly Uuid $teamId,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $bio = null,
        private ?string $avatarFilename = null,
        private VerificationStatus $verificationStatus = VerificationStatus::NotStarted,
        private ?Uuid $identityDocumentId = null,
        private ?Uuid $selfieId = null,
        private ?IdentityDocumentType $documentType = null,
        private ?\DateTimeImmutable $verifiedAt = null,
    ) {
    }

    public static function register(Uuid $id, string $email, string $hashedPassword, Uuid $teamId): self
    {
        $user = new self(id: $id, email: $email, hashedPassword: $hashedPassword, teamId: $teamId);
        $user->recordEvent(new UserRegistered(userId: $id, teamId: $teamId));

        return $user;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function getTeamId(): Uuid
    {
        return $this->teamId;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function updateProfile(?string $firstName, ?string $lastName, string $email, ?string $bio = null): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->bio = $bio;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function changeAvatar(?string $avatarFilename): void
    {
        $this->avatarFilename = $avatarFilename;
    }

    public function getVerificationStatus(): VerificationStatus
    {
        return $this->verificationStatus;
    }

    public function getDocumentType(): ?IdentityDocumentType
    {
        return $this->documentType;
    }

    public function getIdentityDocumentId(): ?Uuid
    {
        return $this->identityDocumentId;
    }

    public function getSelfieId(): ?Uuid
    {
        return $this->selfieId;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    /**
     * Submits the identity document + selfie and (simulated automatic check) marks the user as
     * verified in one step. Records a single IdentityVerified event carrying the file bytes so a
     * listener can persist them to secure storage.
     */
    public function submitAndVerifyIdentity(
        Uuid $documentId,
        Uuid $selfieId,
        IdentityDocumentType $documentType,
        IdentityDocument $document,
        IdentityDocument $selfie,
        \DateTimeImmutable $verifiedAt,
    ): void {
        if (VerificationStatus::Verified === $this->verificationStatus) {
            throw IdentityVerificationException::becauseAlreadyVerified($this->id->toRfc4122());
        }

        $documentFilename = \sprintf('%s.%s', $documentId->toRfc4122(), $document->extension());
        $selfieFilename = \sprintf('%s.%s', $selfieId->toRfc4122(), $selfie->extension());

        $this->identityDocumentId = $documentId;
        $this->selfieId = $selfieId;
        $this->documentType = $documentType;
        $this->verificationStatus = VerificationStatus::Verified;
        $this->verifiedAt = $verifiedAt;

        $this->recordEvent(new IdentityVerified(
            userId: $this->id,
            documentFilename: $documentFilename,
            documentContent: $document->getContent(),
            selfieFilename: $selfieFilename,
            selfieContent: $selfie->getContent(),
        ));
    }
}
