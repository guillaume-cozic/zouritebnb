<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;

/**
 * Lists every reservation of the platform for the admin back-office, most recent check-in first.
 *
 * The accommodation title is fetched through a raw DBAL join on the `accommodation` table
 * (rather than the Accommodation module's classes) to keep the Reservation module decoupled,
 * as required by the vertical-slicing architecture rules.
 *
 * @implements ProviderInterface<AdminReservationOutput>
 */
final readonly class AdminReservationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return AdminReservationOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $sql = <<<'SQL'
            SELECT
                BIN_TO_UUID(r.id) AS id,
                r.guest_name,
                BIN_TO_UUID(r.guest_user_id) AS guest_user_id,
                BIN_TO_UUID(r.accommodation_id) AS accommodation_id,
                a.title AS accommodation_title,
                BIN_TO_UUID(r.team_id) AS team_id,
                r.check_in,
                r.check_out,
                r.status,
                r.total_price,
                r.price_per_night,
                r.applied_discount_percentage
            FROM reservation r
            LEFT JOIN accommodation a ON a.id = r.accommodation_id
            ORDER BY r.check_in DESC
            SQL;

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        $reservations = [];
        foreach ($rows as $row) {
            $output = new AdminReservationOutput();
            $output->id = $row['id'];
            $output->guestName = $row['guest_name'];
            $output->guestUserId = $row['guest_user_id'];
            $output->accommodationId = $row['accommodation_id'];
            $output->accommodationTitle = $row['accommodation_title'];
            $output->teamId = $row['team_id'];
            $output->checkIn = (new \DateTimeImmutable((string) $row['check_in']))->format(\DateTimeInterface::ATOM);
            $output->checkOut = (new \DateTimeImmutable((string) $row['check_out']))->format(\DateTimeInterface::ATOM);
            $output->status = $row['status'];
            $output->totalPrice = (float) $row['total_price'];
            $output->pricePerNight = (float) $row['price_per_night'];
            $output->appliedDiscountPercentage = null === $row['applied_discount_percentage'] ? null : (float) $row['applied_discount_percentage'];
            $reservations[] = $output;
        }

        return $reservations;
    }
}
