<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineConversationRepository::class)]
#[ORM\Table(name: 'conversation')]
class ConversationEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $reservationId = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $accommodationId = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $teamId = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $guestUserId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, MessageEntity> */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: MessageEntity::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sentAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getReservationId(): ?Uuid
    {
        return $this->reservationId;
    }

    public function setReservationId(Uuid $reservationId): static
    {
        $this->reservationId = $reservationId;

        return $this;
    }

    public function getAccommodationId(): ?Uuid
    {
        return $this->accommodationId;
    }

    public function setAccommodationId(Uuid $accommodationId): static
    {
        $this->accommodationId = $accommodationId;

        return $this;
    }

    public function getTeamId(): ?Uuid
    {
        return $this->teamId;
    }

    public function setTeamId(Uuid $teamId): static
    {
        $this->teamId = $teamId;

        return $this;
    }

    public function getGuestUserId(): ?Uuid
    {
        return $this->guestUserId;
    }

    public function setGuestUserId(Uuid $guestUserId): static
    {
        $this->guestUserId = $guestUserId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, MessageEntity> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(MessageEntity $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }
}
