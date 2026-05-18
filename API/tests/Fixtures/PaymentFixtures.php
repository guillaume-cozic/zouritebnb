<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Payment\Infrastructure\Doctrine\PaymentEntity;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

/**
 * Generates a Payment row for every Reservation already loaded by
 * {@see ReservationConversationFixtures}, mapping reservation status to a coherent
 * payment status (pending → pending, confirmed → captured, refused/cancelled → cancelled).
 */
class PaymentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $reservations = $manager->getRepository(ReservationEntity::class)->findAll();

        $counter = 0;
        foreach ($reservations as $reservation) {
            ++$counter;
            $status = match ($reservation->getStatus()) {
                'confirmed' => 'captured',
                'refused', 'cancelled' => 'cancelled',
                default => 'pending',
            };

            $payment = new PaymentEntity()
                ->setId(Uuid::v7())
                ->setReservationId($reservation->getId())
                ->setStripePaymentIntentId(\sprintf('pi_fixture_%06d', $counter))
                ->setStatus($status)
                ->setAmountCents((int) round($reservation->getTotalPrice() * 100))
                ->setCurrency('eur')
                ->setCreatedAt($reservation->getCheckIn()->modify('-7 days'));

            $manager->persist($payment);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ReservationConversationFixtures::class,
        ];
    }
}
