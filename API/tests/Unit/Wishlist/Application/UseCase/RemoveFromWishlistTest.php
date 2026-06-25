<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wishlist\Application\UseCase;

use App\Tests\Unit\Wishlist\Infrastructure\InMemoryAccommodationSummaryProvider;
use App\Tests\Unit\Wishlist\Infrastructure\InMemoryWishlistRepository;
use App\Wishlist\Application\UseCase\AddToWishlist;
use App\Wishlist\Application\UseCase\RemoveFromWishlist;
use App\Wishlist\Domain\Command\AddToWishlistCommand;
use App\Wishlist\Domain\Command\RemoveFromWishlistCommand;
use App\Wishlist\Domain\Entity\WishlistOwner;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RemoveFromWishlistTest extends TestCase
{
    private InMemoryWishlistRepository $repository;
    private RemoveFromWishlist $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryWishlistRepository();
        $this->useCase = new RemoveFromWishlist($this->repository);
    }

    public function test_should_remove_accommodation_from_wishlist(): void
    {
        $accommodationId = Uuid::v7();
        $owner = WishlistOwner::user(Uuid::v7());
        $accommodations = new InMemoryAccommodationSummaryProvider();
        $accommodations->add($accommodationId);
        new AddToWishlist($this->repository, $accommodations)->handle(new AddToWishlistCommand($owner, $accommodationId));

        $this->useCase->handle(new RemoveFromWishlistCommand($owner, $accommodationId));

        self::assertFalse($this->repository->exists($owner, $accommodationId));
    }

    public function test_should_not_fail_when_removing_absent_accommodation(): void
    {
        $owner = WishlistOwner::anonymous(Uuid::v7());

        $this->useCase->handle(new RemoveFromWishlistCommand($owner, Uuid::v7()));

        self::assertCount(0, $this->repository->all());
    }
}
