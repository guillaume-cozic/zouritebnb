<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure\ApiPlatform;

use App\Accommodation\Infrastructure\ApiPlatform\AccommodationListingQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;

final class AccommodationListingQueryTest extends TestCase
{
    /**
     * @return iterable<string, array{?string, bool, string}>
     */
    public static function sortCases(): iterable
    {
        yield 'price ascending pushes nulls last' => ['price_asc', true, 'a.price IS NULL, a.price ASC, a.title ASC'];
        yield 'price descending pushes nulls last' => ['price_desc', true, 'a.price IS NULL, a.price DESC, a.title ASC'];
        yield 'rating uses review stats when available' => ['rating', true, 'average_rating IS NULL, average_rating DESC, review_count DESC, a.title ASC'];
        yield 'rating degrades to title without review stats' => ['rating', false, 'a.title ASC'];
        yield 'unknown sort falls back to title' => ['bogus', true, 'a.title ASC'];
        yield 'absent sort falls back to title' => [null, true, 'a.title ASC'];
    }

    #[DataProvider('sortCases')]
    public function test_should_map_sort_query_to_safe_order_by(?string $sort, bool $withReviewStats, string $expected): void
    {
        $query = new InputBag(null === $sort ? [] : ['sort' => $sort]);
        $listingQuery = new AccommodationListingQuery($this->createStub(Connection::class));

        self::assertSame($expected, $listingQuery->orderByFromQuery($query, $withReviewStats));
    }

    public function test_should_fall_back_to_title_without_a_query(): void
    {
        $listingQuery = new AccommodationListingQuery($this->createStub(Connection::class));

        self::assertSame('a.title ASC', $listingQuery->orderByFromQuery(null, true));
    }
}
