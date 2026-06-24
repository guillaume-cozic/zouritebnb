<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Reservation\Domain\Entity\ReservationPrice;
use Doctrine\DBAL\Connection;

/**
 * Aggregates the platform's financial overview for the admin dashboard from the
 * amounts frozen on each confirmed reservation (revenue, commission/margin, donation).
 *
 * Per-project donations are attributed via a raw DBAL join on the `team` and
 * `solidarity_project` tables (the host team's "coup de cœur", or the platform
 * default project), keeping the Reservation module decoupled.
 *
 * @implements ProviderInterface<AdminDashboardOutput>
 */
final readonly class AdminDashboardProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminDashboardOutput
    {
        $totals = $this->connection->executeQuery(<<<'SQL'
            SELECT
                COALESCE(SUM(total_price), 0) AS revenue,
                COALESCE(SUM(commission_amount), 0) AS margin,
                COALESCE(SUM(donation_amount), 0) AS donated,
                COUNT(*) AS reservations,
                COALESCE(SUM(check_in >= NOW()), 0) AS upcoming
            FROM reservation
            WHERE status = 'confirmed'
            SQL)->fetchAssociative() ?: [];

        $byProject = $this->connection->executeQuery(<<<'SQL'
            SELECT
                BIN_TO_UUID(sp.id) AS project_id,
                JSON_UNQUOTE(JSON_EXTRACT(sp.translations, '$.fr.title')) AS title,
                COALESCE(SUM(r.donation_amount), 0) AS amount
            FROM reservation r
            LEFT JOIN team t ON t.id = r.team_id
            JOIN solidarity_project sp
                ON sp.id = COALESCE(
                    t.favorite_solidarity_project_id,
                    (SELECT id FROM solidarity_project WHERE is_default = 1 LIMIT 1)
                )
            WHERE r.status = 'confirmed'
            GROUP BY sp.id, title
            ORDER BY amount DESC
            SQL)->fetchAllAssociative();

        $output = new AdminDashboardOutput();
        $output->totalRevenue = (float) ($totals['revenue'] ?? 0);
        $output->totalMargin = (float) ($totals['margin'] ?? 0);
        $output->totalDonated = (float) ($totals['donated'] ?? 0);
        $output->confirmedReservations = (int) ($totals['reservations'] ?? 0);
        $output->upcomingStays = (int) ($totals['upcoming'] ?? 0);
        $output->commissionRate = ReservationPrice::COMMISSION_RATE;
        $output->donationRate = ReservationPrice::DONATION_RATE;
        $output->donationsByProject = array_map(
            static fn (array $row): array => [
                'projectId' => $row['project_id'],
                'title' => $row['title'],
                'amount' => (float) $row['amount'],
            ],
            $byProject,
        );

        return $output;
    }
}
