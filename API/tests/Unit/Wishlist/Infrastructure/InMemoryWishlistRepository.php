<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wishlist\Infrastructure;

use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Entity\WishlistOwner;
use App\Wishlist\Domain\Port\WishlistRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryWishlistRepository implements WishlistRepository
{
    /** @var WishlistItem[] */
    private array $items = [];

    public function add(WishlistItem $item): void
    {
        if ($this->exists($item->owner, $item->accommodationId)) {
            return;
        }

        $this->items[] = $item;
    }

    public function remove(WishlistOwner $owner, Uuid $accommodationId): void
    {
        $this->items = array_values(array_filter(
            $this->items,
            fn (WishlistItem $item): bool => !($this->ownerMatches($item->owner, $owner) && $item->accommodationId->equals($accommodationId)),
        ));
    }

    public function exists(WishlistOwner $owner, Uuid $accommodationId): bool
    {
        foreach ($this->items as $item) {
            if ($this->ownerMatches($item->owner, $owner) && $item->accommodationId->equals($accommodationId)) {
                return true;
            }
        }

        return false;
    }

    public function transferOwnership(Uuid $correlationId, Uuid $userId): void
    {
        $userAccommodationIds = [];
        foreach ($this->items as $item) {
            if (null !== $item->owner->userId && $item->owner->userId->equals($userId)) {
                $userAccommodationIds[] = $item->accommodationId->toRfc4122();
            }
        }

        $transferred = [];
        foreach ($this->items as $item) {
            $isAnonymousMatch = null !== $item->owner->correlationId && $item->owner->correlationId->equals($correlationId);
            if (!$isAnonymousMatch) {
                $transferred[] = $item;

                continue;
            }

            // Drop anonymous duplicates the user already saved.
            if (\in_array($item->accommodationId->toRfc4122(), $userAccommodationIds, true)) {
                continue;
            }

            $transferred[] = new WishlistItem($item->id, WishlistOwner::user($userId), $item->accommodationId);
        }

        $this->items = $transferred;
    }

    /** @return WishlistItem[] */
    public function all(): array
    {
        return $this->items;
    }

    private function ownerMatches(WishlistOwner $a, WishlistOwner $b): bool
    {
        return $a->userId?->toRfc4122() === $b->userId?->toRfc4122()
            && $a->correlationId?->toRfc4122() === $b->correlationId?->toRfc4122();
    }
}
