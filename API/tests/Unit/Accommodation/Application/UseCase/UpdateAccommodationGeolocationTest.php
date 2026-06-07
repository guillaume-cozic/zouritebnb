<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationGeolocation;
use App\Accommodation\Domain\Command\UpdateAccommodationGeolocationCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationGeolocationUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationGeolocationTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationGeolocation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationGeolocation($this->repository, $this->eventBus);
    }

    public function test_should_update_geolocation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationGeolocationCommand(
            id: $id,
            latitude: 48.8566,
            longitude: 2.3522,
        ));

        $accommodation = $this->repository->findById($id);
        self::assertNotNull($accommodation->getGeolocation());
        self::assertSame(48.8566, $accommodation->getGeolocation()->latitude());
        self::assertSame(2.3522, $accommodation->getGeolocation()->longitude());
    }

    public function test_should_dispatch_geolocation_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationGeolocationCommand(
            id: $id,
            latitude: 48.8566,
            longitude: 2.3522,
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationGeolocationUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_not_update_geolocation_with_unknown_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UpdateAccommodationGeolocationCommand(
            id: $id,
            latitude: 48.8566,
            longitude: 2.3522,
        ));
    }
}
