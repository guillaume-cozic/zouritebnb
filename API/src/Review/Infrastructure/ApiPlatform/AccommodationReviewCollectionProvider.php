<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\Connection;

/**
 * Lists the reviews left by guests on a given accommodation, most recent first.
 *
 * @implements ProviderInterface<AccommodationReviewOutput>
 */
final readonly class AccommodationReviewCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return AccommodationReviewOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $accommodationId = $uriVariables['accommodationId'] ?? null;
        if (!\is_string($accommodationId) || '' === $accommodationId) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT
                BIN_TO_UUID(r.id) AS id,
                r.rating,
                r.comment,
                r.created_at,
                u.first_name,
                u.last_name
            FROM review r
            LEFT JOIN `user` u ON u.id = r.author_user_id
            WHERE r.type = 'accommodation'
              AND r.subject_accommodation_id = UUID_TO_BIN(:accommodationId)
            ORDER BY r.created_at DESC
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'accommodationId' => $accommodationId,
        ])->fetchAllAssociative();

        $reviews = [];
        foreach ($rows as $row) {
            $output = new AccommodationReviewOutput();
            $output->id = $row['id'];
            $output->rating = (int) $row['rating'];
            $output->comment = $row['comment'];
            $output->authorName = $this->displayName($row['first_name'] ?? null, $row['last_name'] ?? null);
            $output->createdAt = (new \DateTimeImmutable((string) $row['created_at']))->format(\DateTimeInterface::ATOM);
            $reviews[] = $output;
        }

        return $reviews;
    }

    private function displayName(?string $firstName, ?string $lastName): string
    {
        $firstName = null !== $firstName ? trim($firstName) : '';
        $lastName = null !== $lastName ? trim($lastName) : '';

        if ('' === $firstName && '' === $lastName) {
            return 'Voyageur';
        }

        $initial = '' !== $lastName ? ' '.mb_strtoupper(mb_substr($lastName, 0, 1)).'.' : '';

        return '' !== $firstName ? $firstName.$initial : mb_strtoupper(mb_substr($lastName, 0, 1)).'.';
    }
}
