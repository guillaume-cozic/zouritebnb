<?php

declare(strict_types=1);

namespace App\Tests\Integration\Wishlist\Infrastructure;

use App\Tests\Integration\RepositoryTestCase;
use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Entity\WishlistOwner;
use App\Wishlist\Domain\Port\WishlistRepository;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineWishlistRepositoryTest extends RepositoryTestCase
{
    private WishlistRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(WishlistRepository::class);
    }

    public function test_should_add_and_confirm_existence(): void
    {
        $owner = WishlistOwner::user(Uuid::v7());
        $accommodationId = Uuid::v7();

        $this->repository->add(new WishlistItem(Uuid::v7(), $owner, $accommodationId));

        self::assertTrue($this->repository->exists($owner, $accommodationId));
        // A different owner does not see it.
        self::assertFalse($this->repository->exists(WishlistOwner::user(Uuid::v7()), $accommodationId));
        // Same id but anonymous owner is a distinct owner.
        self::assertFalse($this->repository->exists(WishlistOwner::anonymous(Uuid::v7()), $accommodationId));
    }

    public function test_should_remove(): void
    {
        $owner = WishlistOwner::anonymous(Uuid::v7());
        $accommodationId = Uuid::v7();
        $this->repository->add(new WishlistItem(Uuid::v7(), $owner, $accommodationId));

        $this->repository->remove($owner, $accommodationId);

        self::assertFalse($this->repository->exists($owner, $accommodationId));
    }

    public function test_should_transfer_anonymous_items_to_user_and_dedupe(): void
    {
        $correlationId = Uuid::v7();
        $userId = Uuid::v7();
        $shared = Uuid::v7();
        $anonOnly = Uuid::v7();

        // User already has $shared; anonymous has $shared (dup) + $anonOnly.
        $this->repository->add(new WishlistItem(Uuid::v7(), WishlistOwner::user($userId), $shared));
        $this->repository->add(new WishlistItem(Uuid::v7(), WishlistOwner::anonymous($correlationId), $shared));
        $this->repository->add(new WishlistItem(Uuid::v7(), WishlistOwner::anonymous($correlationId), $anonOnly));

        $this->repository->transferOwnership($correlationId, $userId);

        $user = WishlistOwner::user($userId);
        self::assertTrue($this->repository->exists($user, $shared));
        self::assertTrue($this->repository->exists($user, $anonOnly));
        // Nothing remains under the anonymous owner.
        self::assertFalse($this->repository->exists(WishlistOwner::anonymous($correlationId), $shared));
        self::assertFalse($this->repository->exists(WishlistOwner::anonymous($correlationId), $anonOnly));
    }
}
