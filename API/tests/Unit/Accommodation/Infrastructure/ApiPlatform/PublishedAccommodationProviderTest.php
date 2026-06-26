<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use App\Accommodation\Infrastructure\ApiPlatform\AccommodationListingQuery;
use App\Accommodation\Infrastructure\ApiPlatform\PublishedAccommodationProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit-level coverage for the branches of {@see PublishedAccommodationProvider}
 * that the HTTP layer cannot reach (e.g. comma-separated `amenities`, which
 * API Platform rejects as a scalar before the provider runs), plus the
 * no-request fallback path.
 */
final class PublishedAccommodationProviderTest extends TestCase
{
    public function test_should_build_amenity_clauses_filtering_blank_entries(): void
    {
        // amenities[] arrives as an array; blank/whitespace entries are dropped.
        $request = Request::create('/api/accommodations', 'GET', ['amenities' => ['wifi', ' ', 'pool']]);
        $capturedParams = null;

        $provider = $this->buildProvider(
            $request,
            dataRows: [],
            totalItems: 0,
            captureDataParams: static function (array $params) use (&$capturedParams): void {
                $capturedParams = $params;
            },
        );

        $provider->provide($this->operation());

        self::assertNotNull($capturedParams);
        self::assertSame('wifi', $capturedParams['amenity0']);
        self::assertSame('pool', $capturedParams['amenity1']);
        self::assertArrayNotHasKey('amenity2', $capturedParams);
    }

    public function test_should_apply_no_clauses_when_status_is_all_and_no_filters(): void
    {
        $request = Request::create('/api/accommodations', 'GET', ['status' => 'all']);
        $capturedSql = null;

        $provider = $this->buildProvider(
            $request,
            dataRows: [],
            totalItems: 0,
            captureDataSql: static function (string $sql) use (&$capturedSql): void {
                $capturedSql = $sql;
            },
        );

        $provider->provide($this->operation());

        self::assertNotNull($capturedSql);
        self::assertStringContainsString('WHERE 1=1', $capturedSql);
    }

    public function test_should_map_row_fields_and_photo_urls_into_output(): void
    {
        $request = Request::create('/api/accommodations');

        $row = [
            'id' => '11111111-1111-1111-1111-111111111111',
            'title' => 'Mapped Villa',
            'description' => 'Nice place',
            'price' => '199.50',
            'city' => 'Nice',
            'country' => 'France',
            'latitude' => '43.70',
            'longitude' => '7.26',
            'max_guests' => '5',
            'status' => 'published',
            'instant_booking' => 1,
            'type' => 'villa',
            'amenities' => '["wifi","pool"]',
            'thumbnail_filename' => 'thumb.jpg',
        ];

        $provider = $this->buildProvider(
            $request,
            dataRows: [$row],
            totalItems: 1,
            photoRows: [
                ['accommodation_id' => '11111111-1111-1111-1111-111111111111', 'filename' => 'a.jpg'],
                ['accommodation_id' => '11111111-1111-1111-1111-111111111111', 'filename' => 'b.jpg'],
            ],
        );

        $paginator = $provider->provide($this->operation());

        $items = iterator_to_array($paginator);
        self::assertCount(1, $items);

        $output = $items[0];
        self::assertSame('11111111-1111-1111-1111-111111111111', $output->id);
        self::assertSame('Mapped Villa', $output->title);
        self::assertSame(199.5, $output->price);
        self::assertSame(43.70, $output->latitude);
        self::assertSame(7.26, $output->longitude);
        self::assertSame(5, $output->maxGuests);
        self::assertTrue($output->instantBooking);
        self::assertSame('villa', $output->type);
        self::assertSame(['wifi', 'pool'], $output->amenities);
        self::assertSame('/uploads/photos/thumb.jpg', $output->thumbnailUrl);
        self::assertSame(['/uploads/photos/a.jpg', '/uploads/photos/b.jpg'], $output->photoUrls);
    }

    public function test_should_map_null_columns_to_null_outputs(): void
    {
        $request = Request::create('/api/accommodations');

        $row = [
            'id' => '22222222-2222-2222-2222-222222222222',
            'title' => 'Sparse',
            'description' => 'Minimal',
            'price' => null,
            'city' => null,
            'country' => null,
            'latitude' => null,
            'longitude' => null,
            'max_guests' => null,
            'status' => 'published',
            'instant_booking' => 0,
            'type' => null,
            'amenities' => null,
            'thumbnail_filename' => null,
        ];

        $provider = $this->buildProvider(
            $request,
            dataRows: [$row],
            totalItems: 1,
        );

        $output = iterator_to_array($provider->provide($this->operation()))[0];

        self::assertNull($output->price);
        self::assertNull($output->latitude);
        self::assertNull($output->longitude);
        self::assertNull($output->maxGuests);
        self::assertNull($output->amenities);
        self::assertNull($output->thumbnailUrl);
        self::assertSame([], $output->photoUrls);
    }

    public function test_should_keep_amenities_null_when_json_is_not_an_array(): void
    {
        $request = Request::create('/api/accommodations');

        $row = $this->baseRow();
        $row['amenities'] = '"just-a-string"';

        $output = iterator_to_array(
            $this->buildProvider($request, dataRows: [$row], totalItems: 1)->provide($this->operation())
        )[0];

        self::assertNull($output->amenities);
    }

    public function test_should_default_page_one_and_default_items_per_page_without_request(): void
    {
        $capturedParams = null;

        $provider = $this->buildProvider(
            null,
            dataRows: [],
            totalItems: 0,
            captureDataParams: static function (array $params) use (&$capturedParams): void {
                $capturedParams = $params;
            },
        );

        $paginator = $provider->provide($this->operation());

        self::assertNotNull($capturedParams);
        self::assertSame(30, $capturedParams['limit']);
        self::assertSame(0, $capturedParams['offset']);
        self::assertSame(1.0, $paginator->getCurrentPage());
        self::assertSame(30.0, $paginator->getItemsPerPage());
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRow(): array
    {
        return [
            'id' => '33333333-3333-3333-3333-333333333333',
            'title' => 'Base',
            'description' => 'Base desc',
            'price' => '100',
            'city' => 'Paris',
            'country' => 'France',
            'latitude' => '48.85',
            'longitude' => '2.35',
            'max_guests' => '2',
            'status' => 'published',
            'instant_booking' => 0,
            'type' => null,
            'amenities' => null,
            'thumbnail_filename' => null,
        ];
    }

    private function operation(): Operation
    {
        return new GetCollection();
    }

    /**
     * @param list<array<string, mixed>>                  $dataRows
     * @param list<array<string, mixed>>                  $photoRows
     * @param (callable(array<string, mixed>): void)|null $captureDataParams
     * @param (callable(string): void)|null               $captureDataSql
     */
    private function buildProvider(
        ?Request $request,
        array $dataRows,
        int $totalItems,
        array $photoRows = [],
        ?callable $captureDataParams = null,
        ?callable $captureDataSql = null,
    ): PublishedAccommodationProvider {
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $dataResult = $this->createStub(Result::class);
        $dataResult->method('fetchAllAssociative')->willReturn($dataRows);

        $countResult = $this->createStub(Result::class);
        $countResult->method('fetchOne')->willReturn($totalItems);

        $photoResult = $this->createStub(Result::class);
        $photoResult->method('fetchAllAssociative')->willReturn($photoRows);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturnCallback(
            static function (string $sql, array $params = [], array $types = []) use (
                $dataResult,
                $countResult,
                $photoResult,
                $captureDataParams,
                $captureDataSql,
            ): Result {
                if (str_starts_with(ltrim($sql), 'SELECT COUNT(*)')) {
                    return $countResult;
                }

                if (str_contains($sql, 'accommodation_photo p')
                    && str_contains($sql, 'p.accommodation_id IN')) {
                    return $photoResult;
                }

                if (null !== $captureDataParams) {
                    ($captureDataParams)($params);
                }
                if (null !== $captureDataSql) {
                    ($captureDataSql)($sql);
                }

                return $dataResult;
            }
        );

        return new PublishedAccommodationProvider(new AccommodationListingQuery($connection), $requestStack);
    }
}
