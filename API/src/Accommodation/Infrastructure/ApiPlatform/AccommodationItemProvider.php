<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\ApiPlatform\State\EntityProvider;
use Doctrine\DBAL\Connection;

/**
 * @implements ProviderInterface<AccommodationOutput>
 */
final readonly class AccommodationItemProvider implements ProviderInterface
{
    public function __construct(
        private EntityProvider $entityProvider,
        private Connection $connection,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AccommodationOutput
    {
        /** @var AccommodationOutput|null $output */
        $output = $this->entityProvider->provide($operation, $uriVariables, $context);

        if (null === $output || null === $output->id) {
            return $output;
        }

        // Fetch all photos for this accommodation
        $photosSql = <<<'SQL'
            SELECT
                BIN_TO_UUID(p.id) AS id,
                p.filename
            FROM accommodation_photo p
            WHERE p.accommodation_id = UUID_TO_BIN(:accommodationId)
            SQL;

        $rows = $this->connection->executeQuery($photosSql, [
            'accommodationId' => $output->id,
        ])->fetchAllAssociative();

        $photosById = [];
        foreach ($rows as $row) {
            $photosById[$row['id']] = [
                'id' => $row['id'],
                'url' => '/uploads/photos/'.$row['filename'],
            ];
        }

        // Fetch gallery order
        $gallerySql = <<<'SQL'
            SELECT photo_ids
            FROM accommodation_gallery
            WHERE accommodation_id = UUID_TO_BIN(:accommodationId)
            SQL;

        $galleryJson = $this->connection->executeQuery($gallerySql, [
            'accommodationId' => $output->id,
        ])->fetchOne();

        if (false !== $galleryJson && null !== $galleryJson) {
            /** @var string[] $orderedIds */
            $orderedIds = json_decode($galleryJson, true);
            $ordered = [];
            foreach ($orderedIds as $photoId) {
                if (isset($photosById[$photoId])) {
                    $ordered[] = $photosById[$photoId];
                    unset($photosById[$photoId]);
                }
            }
            // Append any photos not in the gallery (shouldn't happen, but safe)
            $output->photos = array_merge($ordered, array_values($photosById));
        } else {
            $output->photos = array_values($photosById);
        }

        // Average rating computed from guest reviews of this accommodation
        $ratingSql = <<<'SQL'
            SELECT AVG(r.rating) AS avg_rating, COUNT(*) AS review_count
            FROM review r
            WHERE r.type = 'accommodation'
              AND r.subject_accommodation_id = UUID_TO_BIN(:accommodationId)
            SQL;

        $ratingRow = $this->connection->executeQuery($ratingSql, [
            'accommodationId' => $output->id,
        ])->fetchAssociative();

        $output->reviewCount = (int) ($ratingRow['review_count'] ?? 0);
        $output->averageRating = null !== ($ratingRow['avg_rating'] ?? null)
            ? round((float) $ratingRow['avg_rating'], 1)
            : null;

        return $output;
    }
}
