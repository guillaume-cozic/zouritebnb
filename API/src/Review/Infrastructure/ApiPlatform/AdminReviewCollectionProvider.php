<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Lists every review of the platform for the admin back-office, most recent first.
 *
 * Author / subject names and the accommodation title are fetched through raw DBAL joins
 * on the `user` and `accommodation` tables (rather than other modules' classes) to keep
 * the Review module decoupled, as required by the vertical-slicing architecture rules.
 *
 * @implements ProviderInterface<AdminReviewOutput>
 */
final readonly class AdminReviewCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<AdminReviewOutput>
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
            $conditions[] = '(r.comment LIKE :search OR author.first_name LIKE :search OR author.last_name LIKE :search OR author.email LIKE :search OR a.title LIKE :search OR subject.first_name LIKE :search OR subject.last_name LIKE :search OR subject.email LIKE :search)';
            $params['search'] = '%'.$search.'%';
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ('' !== $type) {
            $conditions[] = 'r.type = :type';
            $params['type'] = $type;
        }

        $joins = <<<'SQL'
            FROM review r
            LEFT JOIN `user` author ON author.id = r.author_user_id
            LEFT JOIN accommodation a ON a.id = r.subject_accommodation_id
            LEFT JOIN `user` subject ON subject.id = r.subject_user_id
            SQL;

        $where = $conditions ? ' WHERE '.implode(' AND ', $conditions) : '';

        $totalItems = (int) $this->connection->executeQuery(
            'SELECT COUNT(*) '.$joins.$where,
            $params,
            $types,
        )->fetchOne();

        $sql = <<<SQL
            SELECT
                BIN_TO_UUID(r.id) AS id,
                r.type,
                r.rating,
                r.comment,
                r.created_at,
                BIN_TO_UUID(r.author_user_id) AS author_user_id,
                author.first_name AS author_first_name,
                author.last_name AS author_last_name,
                author.email AS author_email,
                BIN_TO_UUID(r.subject_accommodation_id) AS subject_accommodation_id,
                a.title AS subject_accommodation_title,
                BIN_TO_UUID(r.subject_user_id) AS subject_user_id,
                subject.first_name AS subject_first_name,
                subject.last_name AS subject_last_name,
                subject.email AS subject_email
            {$joins}
            {$where}
            ORDER BY r.created_at DESC
            LIMIT :limit OFFSET :offset
            SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            [...$params, 'limit' => $limit, 'offset' => $offset],
            [...$types, 'limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        )->fetchAllAssociative();

        $reviews = [];
        foreach ($rows as $row) {
            $output = new AdminReviewOutput();
            $output->id = $row['id'];
            $output->type = $row['type'];
            $output->rating = (int) $row['rating'];
            $output->comment = $row['comment'];
            $output->createdAt = (new \DateTimeImmutable((string) $row['created_at']))->format(\DateTimeInterface::ATOM);
            $output->authorUserId = $row['author_user_id'];
            $output->authorName = $this->displayName($row['author_first_name'], $row['author_last_name'], $row['author_email']);
            $output->subjectAccommodationId = $row['subject_accommodation_id'];
            $output->subjectAccommodationTitle = $row['subject_accommodation_title'];
            $output->subjectUserId = $row['subject_user_id'];
            $output->subjectUserName = $this->displayName($row['subject_first_name'], $row['subject_last_name'], $row['subject_email']);
            $reviews[] = $output;
        }

        return new TraversablePaginator(new \ArrayIterator($reviews), $page, $limit, $totalItems);
    }

    /**
     * Full name of the user, falling back to their email, or null when no user row was joined.
     */
    private function displayName(?string $firstName, ?string $lastName, ?string $email): ?string
    {
        $fullName = trim(trim((string) $firstName).' '.trim((string) $lastName));

        if ('' !== $fullName) {
            return $fullName;
        }

        return $email;
    }
}
