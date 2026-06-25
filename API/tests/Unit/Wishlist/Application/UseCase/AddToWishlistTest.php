<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wishlist\Application\UseCase;

use App\Tests\Unit\Wishlist\Infrastructure\InMemoryAccommodationSummaryProvider;
use App\Tests\Unit\Wishlist\Infrastructure\InMemoryWishlistRepository;
use App\Wishlist\Application\UseCase\AddToWishlist;
use App\Wishlist\Domain\Command\AddToWishlistCommand;
use App\Wishlist\Domain\Entity\WishlistOwner;
use App\Wishlist\Domain\Exception\WishlistException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AddToWishlistTest extends TestCase
{
    private InMemoryWishlistRepository $repository;
    private InMemoryAccommodationSummaryProvider $accommodations;
    private AddToWishlist $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryWishlistRepository();
        $this->accommodations = new InMemoryAccommodationSummaryProvider();
        $this->useCase = new AddToWishlist($this->repository, $this->accommodations);
    }

    public function test_should_add_accommodation_to_owner_wishlist(): void
    {
        $accommodationId = Uuid::v7();
        $owner = WishlistOwner::user(Uuid::v7());
        $this->accommodations->add($accommodationId);

        $this->useCase->handle(new AddToWishlistCommand($owner, $accommodationId));

        self::assertTrue($this->repository->exists($owner, $accommodationId));
    }

    public function test_should_be_idempotent_when_already_saved(): void
    {
        $accommodationId = Uuid::v7();
        $owner = WishlistOwner::anonymous(Uuid::v7());
        $this->accommodations->add($accommodationId);

        $this->useCase->handle(new AddToWishlistCommand($owner, $accommodationId));
        $this->useCase->handle(new AddToWishlistCommand($owner, $accommodationId));

        self::assertCount(1, $this->repository->all());
    }

    public function test_should_throw_when_accommodation_does_not_exist(): void
    {
        $this->expectException(WishlistException::class);
        $this->expectExceptionMessage('Accommodation not found.');

        $this->useCase->handle(new AddToWishlistCommand(WishlistOwner::user(Uuid::v7()), Uuid::v7()));
    }
}
