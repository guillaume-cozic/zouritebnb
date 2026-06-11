<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;

/**
 * Lists every user of the platform for the admin back-office, sorted by email.
 * The hashed password is never selected nor exposed.
 *
 * Activity counters are fetched through raw DBAL subqueries on the `accommodation` and
 * `reservation` tables (rather than other modules' classes) to keep the User module
 * decoupled, as required by the vertical-slicing architecture rules.
 *
 * @implements ProviderInterface<AdminUserOutput>
 */
final readonly class AdminUserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return AdminUserOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $sql = <<<'SQL'
            SELECT
                BIN_TO_UUID(u.id) AS id,
                u.email,
                u.first_name,
                u.last_name,
                u.roles,
                u.verification_status,
                BIN_TO_UUID(u.team_id) AS team_id,
                (
                    SELECT COUNT(*)
                    FROM accommodation a
                    WHERE a.team_id = u.team_id
                ) AS accommodation_count,
                (
                    SELECT COUNT(*)
                    FROM reservation r
                    WHERE r.guest_user_id = u.id
                ) AS reservation_count
            FROM `user` u
            ORDER BY u.email ASC
            SQL;

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        $users = [];
        foreach ($rows as $row) {
            $output = new AdminUserOutput();
            $output->id = $row['id'];
            $output->email = $row['email'];
            $output->firstName = $row['first_name'];
            $output->lastName = $row['last_name'];
            $output->roles = $this->decodeRoles($row['roles']);
            $output->verificationStatus = $row['verification_status'];
            $output->teamId = $row['team_id'];
            $output->accommodationCount = (int) $row['accommodation_count'];
            $output->reservationCount = (int) $row['reservation_count'];
            $users[] = $output;
        }

        return $users;
    }

    /**
     * @return string[]
     */
    private function decodeRoles(?string $roles): array
    {
        if (null === $roles || '' === $roles) {
            return [];
        }

        $decoded = json_decode($roles, true);

        return \is_array($decoded) ? array_values(array_map(strval(...), $decoded)) : [];
    }
}
