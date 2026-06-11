<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Public, cross-team catalog used by the homepage: published accommodations
 * with search filters (city, guests, price range, amenities) and review stats.
 *
 * @implements ProviderInterface<AccommodationOutput>
 */
final readonly class PublishedAccommodationProvider implements ProviderInterface
{
    public function __construct(
        private AccommodationListingQuery $listingQuery,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        $page = $this->listingQuery->pageFromQuery($query);
        $itemsPerPage = $this->listingQuery->itemsPerPageFromQuery($query);
        $statusFilter = $this->listingQuery->statusFromQuery($query, 'published');

        $clauses = [];
        $params = [];
        $types = [];

        if ('all' !== $statusFilter) {
            $clauses[] = 'a.status = :status';
            $params['status'] = $statusFilter;
        }

        $cityRaw = $query?->get('city');
        if (\is_string($cityRaw) && '' !== trim($cityRaw)) {
            $clauses[] = "REPLACE(LOWER(a.city), '-', ' ') LIKE REPLACE(LOWER(:city), '-', ' ')";
            $params['city'] = '%'.trim($cityRaw).'%';
        }

        $guestsRaw = $query?->get('guests');
        if (null !== $guestsRaw && '' !== $guestsRaw && (int) $guestsRaw > 0) {
            $clauses[] = '(a.max_guests IS NULL OR a.max_guests >= :guests)';
            $params['guests'] = (int) $guestsRaw;
            $types['guests'] = ParameterType::INTEGER;
        }

        $priceMinRaw = $query?->get('priceMin');
        if (null !== $priceMinRaw && '' !== $priceMinRaw && is_numeric($priceMinRaw)) {
            $clauses[] = 'a.price >= :priceMin';
            $params['priceMin'] = (float) $priceMinRaw;
        }

        $priceMaxRaw = $query?->get('priceMax');
        if (null !== $priceMaxRaw && '' !== $priceMaxRaw && is_numeric($priceMaxRaw)) {
            $clauses[] = 'a.price <= :priceMax';
            $params['priceMax'] = (float) $priceMaxRaw;
        }

        // amenities[] (or comma-separated) — accommodation must contain ALL of them
        $amenitiesRaw = $query?->all()['amenities'] ?? [];
        if (\is_string($amenitiesRaw)) {
            $amenitiesRaw = explode(',', $amenitiesRaw);
        }
        $amenities = array_values(array_filter(array_map(
            static fn ($v) => \is_string($v) ? trim($v) : '',
            (array) $amenitiesRaw
        ), static fn ($v) => '' !== $v));
        foreach ($amenities as $i => $code) {
            $paramName = 'amenity'.$i;
            $clauses[] = "JSON_CONTAINS(a.amenities, JSON_QUOTE(:{$paramName}))";
            $params[$paramName] = $code;
        }

        return $this->listingQuery->paginate($clauses, $params, $types, $page, $itemsPerPage, withReviewStats: true);
    }
}
