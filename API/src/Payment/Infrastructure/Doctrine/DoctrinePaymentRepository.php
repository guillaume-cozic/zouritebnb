<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\Doctrine;

use App\Payment\Domain\Entity\Payment;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Port\PaymentRepository as PaymentRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<PaymentEntity>
 */
class DoctrinePaymentRepository extends ServiceEntityRepository implements PaymentRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentEntity::class);
    }

    public function save(Payment $payment): void
    {
        $entity = $this->find($payment->getId()) ?? new PaymentEntity();
        $entity
            ->setId($payment->getId())
            ->setReservationId($payment->getReservationId())
            ->setStripePaymentIntentId($payment->getStripePaymentIntentId())
            ->setStatus($payment->getStatus()->value)
            ->setAmountCents($payment->getAmountCents())
            ->setCurrency($payment->getCurrency())
            ->setCreatedAt($payment->getCreatedAt())
            ->setRefundedAmountCents($payment->getRefundedAmountCents());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?Payment
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?Payment
    {
        $entity = $this->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function findByReservationId(Uuid $reservationId): ?Payment
    {
        $entity = $this->findOneBy(['reservationId' => $reservationId]);

        return $entity ? $this->toDomain($entity) : null;
    }

    private function toDomain(PaymentEntity $entity): Payment
    {
        return new Payment(
            id: $entity->getId(),
            reservationId: $entity->getReservationId(),
            stripePaymentIntentId: $entity->getStripePaymentIntentId(),
            status: PaymentStatus::from($entity->getStatus()),
            amountCents: $entity->getAmountCents(),
            currency: $entity->getCurrency(),
            createdAt: $entity->getCreatedAt(),
            refundedAmountCents: $entity->getRefundedAmountCents(),
        );
    }
}
