<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationDescription;
use App\Accommodation\Domain\Command\UpdateAccommodationDescriptionCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationDescriptionUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationDescriptionTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationDescription $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationDescription($this->repository, $this->eventBus);
    }

    public function test_should_update_description(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Old title', 'Old description', 150.0));

        $this->useCase->handle(new UpdateAccommodationDescriptionCommand(
            id: $id,
            title: 'New title',
            description: 'New description',
        ));

        $accommodation = $this->repository->findById($id);
        self::assertSame('New title', $accommodation->getTitle());
        self::assertSame('New description', $accommodation->getDescription());
    }

    public function test_should_dispatch_description_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Old title', 'Old description', 150.0));

        $this->useCase->handle(new UpdateAccommodationDescriptionCommand(
            id: $id,
            title: 'New title',
            description: 'New description',
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationDescriptionUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_not_update_description_with_unknown_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UpdateAccommodationDescriptionCommand(
            id: $id,
            title: 'New title',
            description: 'New description',
        ));
    }
}
