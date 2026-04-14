<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationWeeklyPromotion;
use App\Accommodation\Domain\Command\UpdateAccommodationWeeklyPromotionCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationWeeklyPromotionUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidWeeklyPromotionException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationWeeklyPromotionTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationWeeklyPromotion $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationWeeklyPromotion($this->repository, $this->eventBus);
    }

    public function testShouldUpdateWeeklyPromotion(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationWeeklyPromotionCommand(accommodationId: $id, weeklyPromotionPercentage: 15.0));

        $accommodation = $this->repository->findById($id);
        self::assertSame(15.0, $accommodation->getWeeklyPromotionPercentage());
    }

    public function testShouldDisablePromotionWithNull(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
        $this->useCase->handle(new UpdateAccommodationWeeklyPromotionCommand(accommodationId: $id, weeklyPromotionPercentage: 20.0));

        $this->useCase->handle(new UpdateAccommodationWeeklyPromotionCommand(accommodationId: $id, weeklyPromotionPercentage: null));

        $accommodation = $this->repository->findById($id);
        self::assertNull($accommodation->getWeeklyPromotionPercentage());
    }

    public function testShouldDispatchWeeklyPromotionUpdatedEvent(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationWeeklyPromotionCommand(accommodationId: $id, weeklyPromotionPercentage: 10.0));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationWeeklyPromotionUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
        self::assertSame(10.0, $events[0]->weeklyPromotionPercentage);
    }

    public function testShouldNotUpdatePromotionWithUnknownAccommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationWeeklyPromotionCommand(accommodationId: $id, weeklyPromotionPercentage: 10.0));
    }

    public function testShouldRejectZeroOrNegative(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidWeeklyPromotionException::class);

        $this->useCase->handle(new UpdateAccommodationWeeklyPromotionCommand(accommodationId: $id, weeklyPromotionPercentage: 0.0));
    }

    public function testShouldRejectAboveHundred(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidWeeklyPromotionException::class);

        $this->useCase->handle(new UpdateAccommodationWeeklyPromotionCommand(accommodationId: $id, weeklyPromotionPercentage: 150.0));
    }
}
