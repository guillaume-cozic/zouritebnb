<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Back-office listing: returns ONLY the accommodations owned by the authenticated
 * user's team, regardless of their publication status.
 *
 * Unlike {@see PublishedAccommodationProvider} (the public, cross-team catalog used by
 * the homepage), this provider is team-scoped and authenticated so an owner never sees
 * another team's accommodations or drafts.
 *
 * @implements ProviderInterface<AccommodationOutput>
 */
final readonly class MyAccommodationsProvider implements ProviderInterface
{
    public function __construct(
        private AccommodationListingQuery $listingQuery,
        private RequestStack $requestStack,
        private CurrentUser $currentUser,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        $page = $this->listingQuery->pageFromQuery($query);
        $itemsPerPage = $this->listingQuery->itemsPerPageFromQuery($query);
        $statusFilter = $this->listingQuery->statusFromQuery($query, 'all');

        // Team scoping is the security boundary: an owner only ever sees their own listings.
        $clauses = ['a.team_id = :teamId'];
        $params = ['teamId' => $this->currentUser->teamId()->toBinary()];
        $types = ['teamId' => ParameterType::BINARY];

        if ('all' !== $statusFilter) {
            $clauses[] = 'a.status = :status';
            $params['status'] = $statusFilter;
        }

        return $this->listingQuery->paginate($clauses, $params, $types, $page, $itemsPerPage, withReviewStats: false);
    }
}
