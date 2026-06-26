<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationStayConstraints;
use App\Accommodation\Domain\Command\UpdateAccommodationStayConstraintsCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationStayConstraintsUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidStayConstraintsException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationStayConstraintsTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationStayConstraints $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationStayConstraints($this->repository, $this->eventBus);
    }

    public function test_should_set_min_and_max_nights(): void
    {
        $id = $this->givenAccommodation();

        $this->useCase->handle(new UpdateAccommodationStayConstraintsCommand($id, minNights: 2, maxNights: 30));

        $accommodation = $this->repository->findById($id);
        self::assertSame(2, $accommodation->getMinNights());
        self::assertSame(30, $accommodation->getMaxNights());
    }

    public function test_should_allow_clearing_both(): void
    {
        $id = $this->givenAccommodation();
        $this->useCase->handle(new UpdateAccommodationStayConstraintsCommand($id, minNights: 3, maxNights: null));

        $this->useCase->handle(new UpdateAccommodationStayConstraintsCommand($id, minNights: null, maxNights: null));

        $accommodation = $this->repository->findById($id);
        self::assertNull($accommodation->getMinNights());
        self::assertNull($accommodation->getMaxNights());
    }

    public function test_should_dispatch_event(): void
    {
        $id = $this->givenAccommodation();

        $this->useCase->handle(new UpdateAccommodationStayConstraintsCommand($id, minNights: 2, maxNights: null));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationStayConstraintsUpdated::class, $events[0]);
    }

    public function test_should_reject_non_positive_nights(): void
    {
        $id = $this->givenAccommodation();

        $this->expectException(InvalidStayConstraintsException::class);

        $this->useCase->handle(new UpdateAccommodationStayConstraintsCommand($id, minNights: 0, maxNights: null));
    }

    public function test_should_reject_min_greater_than_max(): void
    {
        $id = $this->givenAccommodation();

        $this->expectException(InvalidStayConstraintsException::class);

        $this->useCase->handle(new UpdateAccommodationStayConstraintsCommand($id, minNights: 10, maxNights: 5));
    }

    public function test_should_throw_when_accommodation_unknown(): void
    {
        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationStayConstraintsCommand(
            Uuid::fromString('01961e2f-dead-7000-beef-000000000099'),
            minNights: 2,
            maxNights: 5,
        ));
    }

    private function givenAccommodation(): Uuid
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        return $id;
    }
}
