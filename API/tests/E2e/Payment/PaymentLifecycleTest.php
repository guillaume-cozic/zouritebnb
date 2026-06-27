<?php

declare(strict_types=1);

namespace App\Tests\E2e\Payment;

use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Asserts the payment side effects wired to reservation state changes: confirming a
 * reservation captures its payment, refusing or cancelling it cancels the authorization.
 *
 * Domain events are dispatched synchronously in the test env (messenger async → sync://),
 * so the Payment listeners run within the reservation request and we can assert on both
 * the gateway calls and the persisted payment status.
 */
final class PaymentLifecycleTest extends PaymentApiTestCase
{
    private const string TEAM_ID = '00000000-0000-4000-8000-000000000001';

    public function test_should_capture_payment_when_host_confirms_reservation(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'host@example.com', teamId: self::TEAM_ID);
        $reservationId = $this->insertReservation(status: 'pending');
        $this->insertPayment('pi_capture', status: 'authorized', reservationId: $reservationId);

        $client->request('PATCH', '/api/reservations/'.$reservationId.'/confirm', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('captured', $this->paymentStatus('pi_capture'));
        self::assertContains(
            ['type' => 'capture', 'paymentIntentId' => 'pi_capture'],
            $gateway->calls,
        );
    }

    public function test_should_cancel_payment_when_host_refuses_reservation(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'host@example.com', teamId: self::TEAM_ID);
        $reservationId = $this->insertReservation(status: 'pending');
        $this->insertPayment('pi_refuse', status: 'authorized', reservationId: $reservationId);

        $client->request('PATCH', '/api/reservations/'.$reservationId.'/refuse', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('cancelled', $this->paymentStatus('pi_refuse'));
        self::assertContains(
            ['type' => 'cancel', 'paymentIntentId' => 'pi_refuse'],
            $gateway->calls,
        );
    }

    public function test_should_cancel_payment_when_reservation_is_cancelled(): void
    {
        $gateway = new FakePaymentGateway();
        $client = $this->createClientWithFakeGateway($gateway);
        $this->createAuthUser(email: 'host@example.com', teamId: self::TEAM_ID);
        $reservationId = $this->insertReservation(status: 'pending');
        $this->insertPayment('pi_cancel', status: 'authorized', reservationId: $reservationId);

        $client->request('PATCH', '/api/reservations/'.$reservationId.'/cancel', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['message' => 'Annulation par l\'hôte.'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('cancelled', $this->paymentStatus('pi_cancel'));
        self::assertContains(
            ['type' => 'cancel', 'paymentIntentId' => 'pi_cancel'],
            $gateway->calls,
        );
    }

    /**
     * Persists a reservation owned by the host team and returns its UUID (RFC4122).
     */
    private function insertReservation(string $status): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();

        $entity = new ReservationEntity()
            ->setId($id)
            ->setAccommodationId(Uuid::v7())
            ->setTeamId(Uuid::fromString(self::TEAM_ID))
            ->setGuestUserId(null)
            // Future dates so a reservation remains cancellable (cancellation is blocked once the stay started).
            ->setCheckIn(new \DateTimeImmutable('+30 days'))
            ->setCheckOut(new \DateTimeImmutable('+34 days'))
            ->setGuestName('Jean Dupont')
            ->setStatus($status)
            ->setTotalPrice(400.0)
            ->setPricePerNight(100.0)
            ->setAppliedDiscountPercentage(null)
            ->setCommissionAmount(round(400.0 * ReservationPrice::COMMISSION_RATE, 2))
            ->setDonationAmount(round(400.0 * ReservationPrice::DONATION_RATE, 2));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }
}
