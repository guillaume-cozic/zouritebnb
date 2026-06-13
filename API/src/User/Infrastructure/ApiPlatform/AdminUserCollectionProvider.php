<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

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
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<AdminUserOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        [$page, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        $filters = $context['filters'] ?? [];
        $conditions = [];
        $params = [];
        $types = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ('' !== $search) {
            $conditions[] = '(u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)';
            $params['search'] = '%'.$search.'%';
        }

        // "hosts" = users whose team owns at least one accommodation, "travelers" = the others.
        $role = trim((string) ($filters['role'] ?? ''));
        if ('hosts' === $role) {
            $conditions[] = 'EXISTS (SELECT 1 FROM accommodation a WHERE a.team_id = u.team_id)';
        } elseif ('travelers' === $role) {
            $conditions[] = 'NOT EXISTS (SELECT 1 FROM accommodation a WHERE a.team_id = u.team_id)';
        }

        $where = $conditions ? ' WHERE '.implode(' AND ', $conditions) : '';

        $totalItems = (int) $this->connection->executeQuery(
            'SELECT COUNT(*) FROM `user` u'.$where,
            $params,
            $types,
        )->fetchOne();

        $sql = <<<SQL
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
            {$where}
            ORDER BY u.email ASC
            LIMIT :limit OFFSET :offset
            SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            [...$params, 'limit' => $limit, 'offset' => $offset],
            [...$types, 'limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        )->fetchAllAssociative();

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

        return new TraversablePaginator(new \ArrayIterator($users), $page, $limit, $totalItems);
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
