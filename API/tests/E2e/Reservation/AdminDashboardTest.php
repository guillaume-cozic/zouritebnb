<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class AdminDashboardTest extends ReservationApiTestCase
{
    use AuthenticatedClientTrait;

    public function test_should_return_the_financial_overview_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $projectTitle = 'Reforestation de Rodrigues';
        $this->insertDefaultSolidarityProject($projectTitle);

        // Two confirmed reservations (400 € each → 8% = 32 €, 7% = 28 €): one past, one
        // upcoming; plus one pending (ignored).
        $this->insertReservation(
            status: 'confirmed',
            guestName: 'Alice',
            checkIn: '2000-01-01T15:00:00+00:00',
            checkOut: '2000-01-05T11:00:00+00:00',
        );
        $this->insertReservation(
            status: 'confirmed',
            guestName: 'Bob',
            checkIn: '2099-01-01T15:00:00+00:00',
            checkOut: '2099-01-05T11:00:00+00:00',
        );
        $this->insertReservation(
            status: 'pending',
            guestName: 'Carol',
            checkIn: '2099-02-01T15:00:00+00:00',
            checkOut: '2099-02-05T11:00:00+00:00',
        );

        $response = self::createClient()->request('GET', '/api/admin/dashboard', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();

        self::assertSame(800.0, (float) $data['totalRevenue']);
        self::assertSame(64.0, (float) $data['totalMargin']);
        self::assertSame(56.0, (float) $data['totalDonated']);
        self::assertSame(2, $data['confirmedReservations']);
        self::assertSame(1, $data['upcomingStays']);
        self::assertSame(0.08, (float) $data['commissionRate']);
        self::assertSame(0.07, (float) $data['donationRate']);

        self::assertCount(1, $data['donationsByProject']);
        self::assertSame($projectTitle, $data['donationsByProject'][0]['title']);
        self::assertSame(56.0, (float) $data['donationsByProject'][0]['amount']);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('GET', '/api/admin/dashboard', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        self::createClient()->request('GET', '/api/admin/dashboard');

        self::assertResponseStatusCodeSame(401);
    }

    private function insertDefaultSolidarityProject(string $title): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new SolidarityProjectEntity()
            ->setId(Uuid::v7())
            ->setTranslations([
                'fr' => ['title' => $title, 'description' => 'Description du projet par défaut.', 'keyFigures' => []],
            ])
            ->setStatus('active')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsDefault(true);

        $em->persist($entity);
        $em->flush();
    }
}
