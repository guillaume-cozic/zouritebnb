<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationPricePeriods;
use App\Accommodation\Domain\Command\UpdateAccommodationPricePeriodsCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationPricePeriodsUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidPricePeriodException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationPricePeriodsTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationPricePeriods $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationPricePeriods($this->repository, $this->eventBus);
    }

    private function givenAccommodation(Uuid $id): void
    {
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
    }

    public function test_should_replace_price_periods(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $periods = [
            ['startDate' => '2026-07-01', 'endDate' => '2026-08-31', 'pricePerNight' => 250.0],
            ['startDate' => '2026-12-20', 'endDate' => '2027-01-05', 'pricePerNight' => 300.0],
        ];
        $this->useCase->handle(new UpdateAccommodationPricePeriodsCommand($id, $periods));

        $accommodation = $this->repository->findById($id);
        self::assertSame($periods, $accommodation->getPricePeriods()->toArray());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationPricePeriodsUpdated::class, $events[0]);
    }

    public function test_should_clear_price_periods_with_empty_list(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);
        $this->useCase->handle(new UpdateAccommodationPricePeriodsCommand($id, [['startDate' => '2026-07-01', 'endDate' => '2026-08-31', 'pricePerNight' => 250.0]]));

        $this->useCase->handle(new UpdateAccommodationPricePeriodsCommand($id, []));

        self::assertTrue($this->repository->findById($id)->getPricePeriods()->isEmpty());
    }

    public function test_should_reject_period_with_end_before_start(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->expectException(InvalidPricePeriodException::class);

        $this->useCase->handle(new UpdateAccommodationPricePeriodsCommand($id, [['startDate' => '2026-08-31', 'endDate' => '2026-07-01', 'pricePerNight' => 250.0]]));
    }

    public function test_should_reject_period_with_non_positive_price(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->expectException(InvalidPricePeriodException::class);

        $this->useCase->handle(new UpdateAccommodationPricePeriodsCommand($id, [['startDate' => '2026-07-01', 'endDate' => '2026-08-31', 'pricePerNight' => 0.0]]));
    }

    public function test_should_throw_when_accommodation_missing(): void
    {
        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationPricePeriodsCommand(Uuid::fromString('01961e2f-dead-7000-beef-0000000000ee'), []));
    }
}
