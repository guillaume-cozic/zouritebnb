<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationDynamicPricing;
use App\Accommodation\Domain\Command\UpdateAccommodationDynamicPricingCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationDynamicPricingUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidDynamicPricingException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationDynamicPricingTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationDynamicPricing $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationDynamicPricing($this->repository, $this->eventBus);
    }

    private function givenAccommodation(Uuid $id): void
    {
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
    }

    public function test_should_update_weekend_and_last_minute(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->useCase->handle(new UpdateAccommodationDynamicPricingCommand($id, 20.0, 15.0, 7));

        $accommodation = $this->repository->findById($id);
        self::assertSame(20.0, $accommodation->getWeekendSurchargePercentage());
        self::assertSame(15.0, $accommodation->getLastMinuteDiscountPercentage());
        self::assertSame(7, $accommodation->getLastMinuteDays());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationDynamicPricingUpdated::class, $events[0]);
    }

    public function test_should_disable_with_nulls(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);
        $this->useCase->handle(new UpdateAccommodationDynamicPricingCommand($id, 20.0, 15.0, 7));

        $this->useCase->handle(new UpdateAccommodationDynamicPricingCommand($id, null, null, null));

        $accommodation = $this->repository->findById($id);
        self::assertNull($accommodation->getWeekendSurchargePercentage());
        self::assertNull($accommodation->getLastMinuteDiscountPercentage());
        self::assertNull($accommodation->getLastMinuteDays());
    }

    public function test_should_reject_incomplete_last_minute(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->expectException(InvalidDynamicPricingException::class);

        $this->useCase->handle(new UpdateAccommodationDynamicPricingCommand($id, null, 15.0, null));
    }

    public function test_should_reject_weekend_surcharge_out_of_bounds(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->expectException(InvalidDynamicPricingException::class);

        $this->useCase->handle(new UpdateAccommodationDynamicPricingCommand($id, 600.0, null, null));
    }

    public function test_should_throw_when_accommodation_missing(): void
    {
        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationDynamicPricingCommand(Uuid::fromString('01961e2f-dead-7000-beef-0000000000ee'), 20.0, null, null));
    }
}
