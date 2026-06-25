<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wishlist\Application\UseCase;

use App\Tests\Unit\Wishlist\Infrastructure\InMemoryWishlistRepository;
use App\Wishlist\Application\UseCase\MergeAnonymousWishlist;
use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Entity\WishlistOwner;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class MergeAnonymousWishlistTest extends TestCase
{
    private InMemoryWishlistRepository $repository;
    private MergeAnonymousWishlist $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryWishlistRepository();
        $this->useCase = new MergeAnonymousWishlist($this->repository);
    }

    public function test_should_reattach_anonymous_items_to_the_user(): void
    {
        $correlationId = Uuid::v7();
        $userId = Uuid::v7();
        $accommodationId = Uuid::v7();
        $anonymous = WishlistOwner::anonymous($correlationId);
        $this->repository->add(new WishlistItem(Uuid::v7(), $anonymous, $accommodationId));

        $this->useCase->handle($correlationId, $userId);

        self::assertFalse($this->repository->exists($anonymous, $accommodationId));
        self::assertTrue($this->repository->exists(WishlistOwner::user($userId), $accommodationId));
    }

    public function test_should_drop_anonymous_duplicate_already_saved_by_user(): void
    {
        $correlationId = Uuid::v7();
        $userId = Uuid::v7();
        $accommodationId = Uuid::v7();
        // Same accommodation saved both anonymously and by the user.
        $this->repository->add(new WishlistItem(Uuid::v7(), WishlistOwner::user($userId), $accommodationId));
        $this->repository->add(new WishlistItem(Uuid::v7(), WishlistOwner::anonymous($correlationId), $accommodationId));

        $this->useCase->handle($correlationId, $userId);

        // Only the user's single item remains — no duplicate.
        self::assertCount(1, $this->repository->all());
        self::assertTrue($this->repository->exists(WishlistOwner::user($userId), $accommodationId));
    }

    public function test_should_leave_other_owners_untouched(): void
    {
        $correlationId = Uuid::v7();
        $userId = Uuid::v7();
        $otherCorrelationId = Uuid::v7();
        $otherAccommodation = Uuid::v7();
        $this->repository->add(new WishlistItem(Uuid::v7(), WishlistOwner::anonymous($otherCorrelationId), $otherAccommodation));

        $this->useCase->handle($correlationId, $userId);

        self::assertTrue($this->repository->exists(WishlistOwner::anonymous($otherCorrelationId), $otherAccommodation));
    }
}
