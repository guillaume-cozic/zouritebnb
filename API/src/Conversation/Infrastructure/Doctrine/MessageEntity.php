<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'conversation_message')]
class MessageEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: ConversationEntity::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ConversationEntity $conversation = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $authorUserId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentFilename = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column]
    private bool $isSystem = false;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getConversation(): ?ConversationEntity
    {
        return $this->conversation;
    }

    public function setConversation(?ConversationEntity $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getAuthorUserId(): ?Uuid
    {
        return $this->authorUserId;
    }

    public function setAuthorUserId(?Uuid $authorUserId): static
    {
        $this->authorUserId = $authorUserId;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getAttachmentFilename(): ?string
    {
        return $this->attachmentFilename;
    }

    public function setAttachmentFilename(?string $attachmentFilename): static
    {
        $this->attachmentFilename = $attachmentFilename;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;

        return $this;
    }
}
