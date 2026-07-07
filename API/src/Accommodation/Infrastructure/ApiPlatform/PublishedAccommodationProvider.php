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
 * with search filters (keyword, city, guests, price range, amenities) and review stats.
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

        // Full-text keyword search on title + description: every word of the
        // query must appear in at least one of the two fields. LIKE (not
        // MATCH...AGAINST) because InnoDB fulltext indexes are only updated at
        // commit time, which would make the filter blind to rows inserted in
        // the current transaction (e.g. E2E fixtures) and to short words.
        $qRaw = $query?->get('q');
        if (\is_string($qRaw) && '' !== trim($qRaw)) {
            $words = preg_split('/\s+/', trim($qRaw), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($words as $i => $word) {
                $paramName = 'q'.$i;
                $clauses[] = "(LOWER(a.title) LIKE LOWER(:{$paramName}) OR LOWER(a.description) LIKE LOWER(:{$paramName}))";
                $params[$paramName] = '%'.$word.'%';
            }
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

        // Accommodation type (category).
        $typeRaw = $query?->get('type');
        if (\is_string($typeRaw) && '' !== trim($typeRaw)) {
            $clauses[] = 'a.type = :type';
            $params['type'] = trim($typeRaw);
        }

        // Instant booking: keep only accommodations that auto-confirm bookings.
        $instantBookingRaw = $query?->get('instantBooking');
        if (\in_array($instantBookingRaw, ['1', 'true', true], true)) {
            $clauses[] = 'a.instant_booking = 1';
        }

        // Availability window: keep only accommodations free for the whole range.
        // Day-granular comparison so a same-day turnover stays available (a stay
        // leaving on the requested check-in day, or arriving on the requested
        // check-out day, does not block it) — mirroring the booking-time overlap
        // rule in DoctrineReservationRepository::hasOverlappingReservation().
        $checkIn = self::parseDate($query?->get('checkIn'));
        $checkOut = self::parseDate($query?->get('checkOut'));
        if (null !== $checkIn && null !== $checkOut && $checkIn < $checkOut) {
            $clauses[] = <<<SQL
                NOT EXISTS (
                    SELECT 1 FROM reservation res
                    WHERE res.accommodation_id = a.id
                      AND res.status IN ('pending', 'confirmed')
                      AND DATE(res.check_in) < :checkOut
                      AND DATE(res.check_out) > :checkIn
                )
                SQL;
            $params['checkIn'] = $checkIn;
            $params['checkOut'] = $checkOut;

            // A dated search also honours the stay-length constraints: keep only
            // accommodations whose min/max nights fit the requested number of nights.
            $nights = (int) (new \DateTimeImmutable($checkIn))->diff(new \DateTimeImmutable($checkOut))->days;
            $clauses[] = '(a.min_nights IS NULL OR a.min_nights <= :nights)';
            $clauses[] = '(a.max_nights IS NULL OR a.max_nights >= :nights)';
            $params['nights'] = $nights;
            $types['nights'] = ParameterType::INTEGER;
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

        // Map bounding box ("search this area"): keep only accommodations whose
        // coordinates fall inside the visible viewport. BETWEEN excludes rows with a
        // NULL latitude/longitude, so non-geolocated listings drop out of a zone search.
        $north = $query?->get('north');
        $south = $query?->get('south');
        $east = $query?->get('east');
        $west = $query?->get('west');
        if (is_numeric($north) && is_numeric($south) && is_numeric($east) && is_numeric($west)) {
            $clauses[] = 'a.latitude BETWEEN :south AND :north AND a.longitude BETWEEN :west AND :east';
            $params['south'] = (float) $south;
            $params['north'] = (float) $north;
            $params['west'] = (float) $west;
            $params['east'] = (float) $east;
        }

        $orderBy = $this->listingQuery->orderByFromQuery($query, withReviewStats: true);

        return $this->listingQuery->paginate($clauses, $params, $types, $page, $itemsPerPage, withReviewStats: true, orderBy: $orderBy);
    }

    /**
     * Validates a `YYYY-MM-DD` query value, returning it untouched when valid
     * and null otherwise (malformed dates are ignored, like the other filters).
     */
    private static function parseDate(mixed $raw): ?string
    {
        if (!\is_string($raw)) {
            return null;
        }

        $value = trim($raw);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value
            ? $value
            : null;
    }
}
