<?php

declare(strict_types=1);

namespace App\Team\Domain\Entity;

use App\Team\Domain\Exception\InvalidInvitationException;
use Symfony\Component\Uid\Uuid;

final class TeamInvitation
{
    public function __construct(
        private readonly Uuid $id,
        private readonly Uuid $teamId,
        private readonly string $email,
        private InvitationStatus $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        if ('' === trim($this->email)) {
            throw InvalidInvitationException::becauseEmptyEmail();
        }

        if (!filter_var($this->email, \FILTER_VALIDATE_EMAIL)) {
            throw InvalidInvitationException::becauseInvalidEmailFormat($this->email);
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTeamId(): Uuid
    {
        return $this->teamId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): InvitationStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function cancel(): void
    {
        if (InvitationStatus::Pending !== $this->status) {
            throw InvalidInvitationException::becauseAlreadyFinalized();
        }

        $this->status = InvitationStatus::Cancelled;
    }
}
