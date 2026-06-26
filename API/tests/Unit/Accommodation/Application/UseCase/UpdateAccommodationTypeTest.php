<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationType;
use App\Accommodation\Domain\Command\UpdateAccommodationTypeCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\AccommodationType;
use App\Accommodation\Domain\Event\AccommodationTypeUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidAccommodationTypeException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationTypeTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationType $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationType($this->repository, $this->eventBus);
    }

    public function test_should_default_to_null(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        self::assertNull($this->repository->findById($id)->getType());
    }

    public function test_should_set_the_type(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationTypeCommand(accommodationId: $id, type: 'villa'));

        self::assertSame(AccommodationType::Villa, $this->repository->findById($id)->getType());
    }

    public function test_should_clear_the_type_with_null(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0, type: AccommodationType::Villa));

        $this->useCase->handle(new UpdateAccommodationTypeCommand(accommodationId: $id, type: null));

        self::assertNull($this->repository->findById($id)->getType());
    }

    public function test_should_dispatch_type_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationTypeCommand(accommodationId: $id, type: 'studio'));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationTypeUpdated::class, $events[0]);
    }

    public function test_should_reject_unknown_type(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidAccommodationTypeException::class);

        $this->useCase->handle(new UpdateAccommodationTypeCommand(accommodationId: $id, type: 'castle'));
    }

    public function test_should_throw_when_accommodation_unknown(): void
    {
        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationTypeCommand(
            accommodationId: Uuid::fromString('01961e2f-dead-7000-beef-000000000099'),
            type: 'villa',
        ));
    }
}
