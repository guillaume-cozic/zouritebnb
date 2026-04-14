<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Entity\AggregateRoot;
use App\User\Domain\Event\UserRegistered;
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

    public function updateProfile(?string $firstName, ?string $lastName, string $email): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
    }
}
