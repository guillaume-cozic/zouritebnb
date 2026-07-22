<?php

declare(strict_types=1);

namespace App\Tests\Integration\Donation\Infrastructure;

use App\Donation\Domain\Entity\Donation;
use App\Donation\Domain\Entity\DonationStatus;
use App\Donation\Domain\Port\DonationRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineDonationRepositoryTest extends RepositoryTestCase
{
    private DonationRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(DonationRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $solidarityProjectId = Uuid::v4();
        $createdAt = new \DateTimeImmutable('2026-07-01 10:30:00');
        $donation = new Donation(
            id: $id,
            solidarityProjectId: $solidarityProjectId,
            stripePaymentIntentId: 'pi_donation_123',
            status: DonationStatus::Pending,
            amountCents: 2500,
            currency: 'eur',
            createdAt: $createdAt,
        );

        $this->repository->save($donation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertEquals($solidarityProjectId, $found->getSolidarityProjectId());
        self::assertSame('pi_donation_123', $found->getStripePaymentIntentId());
        self::assertSame(DonationStatus::Pending, $found->getStatus());
        self::assertSame(2500, $found->getAmountCents());
        self::assertSame('eur', $found->getCurrency());
        self::assertEquals($createdAt, $found->getCreatedAt());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_donation(): void
    {
        $id = Uuid::v4();
        $donation = new Donation(
            id: $id,
            solidarityProjectId: Uuid::v4(),
            stripePaymentIntentId: 'pi_donation_update',
            status: DonationStatus::Pending,
            amountCents: 5000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable('2026-07-02 09:00:00'),
        );
        $this->repository->save($donation);

        $donation->markPaid();
        $this->repository->save($donation);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame(DonationStatus::Paid, $found->getStatus());
        self::assertSame('pi_donation_update', $found->getStripePaymentIntentId());
        self::assertSame(5000, $found->getAmountCents());
    }

    public function test_should_find_by_payment_intent_id(): void
    {
        $id = Uuid::v4();
        $donation = new Donation(
            id: $id,
            solidarityProjectId: Uuid::v4(),
            stripePaymentIntentId: 'pi_donation_lookup',
            status: DonationStatus::Paid,
            amountCents: 10000,
            currency: 'eur',
            createdAt: new \DateTimeImmutable('2026-07-03 14:45:00'),
        );
        $this->repository->save($donation);

        $found = $this->repository->findByPaymentIntentId('pi_donation_lookup');

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('pi_donation_lookup', $found->getStripePaymentIntentId());
        self::assertSame(DonationStatus::Paid, $found->getStatus());
    }

    public function test_should_return_null_when_payment_intent_id_not_found(): void
    {
        $result = $this->repository->findByPaymentIntentId('pi_donation_missing');

        self::assertNull($result);
    }
}
