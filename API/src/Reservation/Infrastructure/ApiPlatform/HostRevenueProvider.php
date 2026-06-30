<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Domain\Port\Clock;
use App\Shared\Infrastructure\Security\CurrentUser;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * Builds the host's revenue dashboard from the amounts frozen on each of their team's
 * confirmed reservations. The host earns the full stay price (total_price); the platform
 * commission and the solidarity donation are surcharges paid on top by the guest, so they
 * never reduce the host payout.
 *
 * A payout is "pending" while the stay is not over yet (check_out in the future) and
 * "available" once the guest has checked out. Everything is derived on the fly via raw
 * DBAL — no payout entity is persisted and no real bank transfer happens (the IBAN is just
 * stored). The accommodation title is fetched through a raw join, keeping the Reservation
 * module decoupled from the Accommodation module.
 *
 * @implements ProviderInterface<HostRevenueOutput>
 */
final readonly class HostRevenueProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
        private CurrentUser $currentUser,
        private Clock $clock,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HostRevenueOutput
    {
        $teamId = $this->currentUser->teamId();
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $params = ['teamId' => $teamId, 'now' => $now];
        $types = ['teamId' => UuidType::NAME];

        $totals = $this->connection->executeQuery(<<<'SQL'
            SELECT
                COALESCE(SUM(total_price), 0) AS total_earned,
                COALESCE(SUM(CASE WHEN check_out >= :now THEN total_price ELSE 0 END), 0) AS pending_amount,
                COUNT(*) AS reservations,
                COALESCE(SUM(check_out >= :now), 0) AS upcoming
            FROM reservation
            WHERE status = 'confirmed' AND team_id = :teamId
            SQL, $params, $types)->fetchAssociative() ?: [];

        $byAccommodation = $this->connection->executeQuery(<<<'SQL'
            SELECT
                BIN_TO_UUID(r.accommodation_id) AS accommodation_id,
                a.title AS title,
                COALESCE(SUM(r.total_price), 0) AS amount,
                COUNT(*) AS reservations
            FROM reservation r
            LEFT JOIN accommodation a ON a.id = r.accommodation_id
            WHERE r.status = 'confirmed' AND r.team_id = :teamId
            GROUP BY r.accommodation_id, a.title
            ORDER BY amount DESC
            SQL, $params, $types)->fetchAllAssociative();

        $byMonth = $this->connection->executeQuery(<<<'SQL'
            SELECT
                DATE_FORMAT(check_out, '%Y-%m') AS month,
                COALESCE(SUM(total_price), 0) AS amount
            FROM reservation
            WHERE status = 'confirmed' AND team_id = :teamId
            GROUP BY month
            ORDER BY month DESC
            SQL, $params, $types)->fetchAllAssociative();

        $payouts = $this->connection->executeQuery(<<<'SQL'
            SELECT
                BIN_TO_UUID(r.id) AS reservation_id,
                a.title AS accommodation_title,
                r.guest_name,
                r.check_in,
                r.check_out,
                r.total_price,
                (r.check_out >= :now) AS is_pending
            FROM reservation r
            LEFT JOIN accommodation a ON a.id = r.accommodation_id
            WHERE r.status = 'confirmed' AND r.team_id = :teamId
            ORDER BY r.check_out DESC
            SQL, $params, $types)->fetchAllAssociative();

        $output = new HostRevenueOutput();
        $output->totalEarned = (float) ($totals['total_earned'] ?? 0);
        $output->pendingAmount = (float) ($totals['pending_amount'] ?? 0);
        $output->availableAmount = round($output->totalEarned - $output->pendingAmount, 2);
        $output->confirmedReservations = (int) ($totals['reservations'] ?? 0);
        $output->upcomingStays = (int) ($totals['upcoming'] ?? 0);

        $output->byAccommodation = array_map(
            static fn (array $row): array => [
                'accommodationId' => $row['accommodation_id'],
                'title' => $row['title'],
                'amount' => (float) $row['amount'],
                'reservations' => (int) $row['reservations'],
            ],
            $byAccommodation,
        );

        $output->byMonth = array_map(
            static fn (array $row): array => [
                'month' => (string) $row['month'],
                'amount' => (float) $row['amount'],
            ],
            $byMonth,
        );

        $output->payouts = array_map(
            static fn (array $row): array => [
                'reservationId' => $row['reservation_id'],
                'accommodationTitle' => $row['accommodation_title'],
                'guestName' => $row['guest_name'],
                'checkIn' => (new \DateTimeImmutable((string) $row['check_in']))->format(\DateTimeInterface::ATOM),
                'checkOut' => (new \DateTimeImmutable((string) $row['check_out']))->format(\DateTimeInterface::ATOM),
                'amount' => (float) $row['total_price'],
                'status' => $row['is_pending'] ? 'pending' : 'available',
            ],
            $payouts,
        );

        return $output;
    }
}
