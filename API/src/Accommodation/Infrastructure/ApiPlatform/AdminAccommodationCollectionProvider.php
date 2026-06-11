<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;

/**
 * Lists every accommodation of the platform for the admin back-office, sorted by title.
 *
 * The host email is fetched through a raw DBAL subquery on the `user` table
 * (rather than the User module's classes) to keep the Accommodation module decoupled,
 * as required by the vertical-slicing architecture rules.
 *
 * @implements ProviderInterface<AdminAccommodationOutput>
 */
final readonly class AdminAccommodationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return AdminAccommodationOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $sql = <<<'SQL'
            SELECT
                BIN_TO_UUID(a.id) AS id,
                a.title,
                a.status,
                a.price,
                a.city,
                a.bedrooms,
                a.max_guests,
                a.weekly_promotion_percentage,
                BIN_TO_UUID(a.team_id) AS team_id,
                (
                    SELECT u.email
                    FROM `user` u
                    WHERE u.team_id = a.team_id
                    ORDER BY u.email ASC
                    LIMIT 1
                ) AS host_email
            FROM accommodation a
            ORDER BY a.title ASC
            SQL;

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        $accommodations = [];
        foreach ($rows as $row) {
            $output = new AdminAccommodationOutput();
            $output->id = $row['id'];
            $output->title = $row['title'];
            $output->status = $row['status'];
            $output->price = null === $row['price'] ? null : (float) $row['price'];
            $output->city = $row['city'];
            $output->bedrooms = null === $row['bedrooms'] ? null : (int) $row['bedrooms'];
            $output->maxGuests = null === $row['max_guests'] ? null : (int) $row['max_guests'];
            $output->weeklyPromotionPercentage = null === $row['weekly_promotion_percentage'] ? null : (float) $row['weekly_promotion_percentage'];
            $output->teamId = $row['team_id'];
            $output->hostEmail = $row['host_email'];
            $accommodations[] = $output;
        }

        return $accommodations;
    }
}
