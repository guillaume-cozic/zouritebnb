<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrinePaymentRepository::class)]
#[ORM\Table(name: 'payment')]
#[ORM\Index(name: 'IDX_PAYMENT_RESERVATION', columns: ['reservation_id'])]
#[ORM\Index(name: 'IDX_PAYMENT_STRIPE_INTENT', columns: ['stripe_payment_intent_id'])]
class PaymentEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: UuidType::NAME, name: 'reservation_id', nullable: true)]
    private ?Uuid $reservationId = null;

    #[ORM\Column(name: 'stripe_payment_intent_id', length: 255, unique: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(name: 'amount_cents', type: Types::INTEGER)]
    private ?int $amountCents = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function setReservationId(?Uuid $reservationId): static
    {
        $this->reservationId = $reservationId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents): static
    {
        $this->amountCents = $amountCents;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

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
}
