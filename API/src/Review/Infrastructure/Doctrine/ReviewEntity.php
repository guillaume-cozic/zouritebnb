<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\UniqueConstraint(name: 'uniq_review_author_reservation_type', columns: ['author_user_id', 'reservation_id', 'type'])]
class ReviewEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $reservationId = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $authorUserId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $subjectAccommodationId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $subjectUserId = null;

    #[ORM\Column]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $hostReply = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $hostReplyAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getAuthorUserId(): ?Uuid
    {
        return $this->authorUserId;
    }

    public function setAuthorUserId(Uuid $authorUserId): static
    {
        $this->authorUserId = $authorUserId;

        return $this;
    }

    public function getSubjectAccommodationId(): ?Uuid
    {
        return $this->subjectAccommodationId;
    }

    public function setSubjectAccommodationId(?Uuid $subjectAccommodationId): static
    {
        $this->subjectAccommodationId = $subjectAccommodationId;

        return $this;
    }

    public function getSubjectUserId(): ?Uuid
    {
        return $this->subjectUserId;
    }

    public function setSubjectUserId(?Uuid $subjectUserId): static
    {
        $this->subjectUserId = $subjectUserId;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

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

    public function getHostReply(): ?string
    {
        return $this->hostReply;
    }

    public function setHostReply(?string $hostReply): static
    {
        $this->hostReply = $hostReply;

        return $this;
    }

    public function getHostReplyAt(): ?\DateTimeImmutable
    {
        return $this->hostReplyAt;
    }

    public function setHostReplyAt(?\DateTimeImmutable $hostReplyAt): static
    {
        $this->hostReplyAt = $hostReplyAt;

        return $this;
    }
}
