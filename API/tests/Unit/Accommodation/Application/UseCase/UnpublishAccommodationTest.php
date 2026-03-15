<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UnpublishAccommodation;
use App\Accommodation\Domain\Command\UnpublishAccommodationCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\AccommodationStatus;
use App\Accommodation\Domain\Event\AccommodationUnpublished;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UnpublishAccommodationTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UnpublishAccommodation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UnpublishAccommodation($this->repository, $this->eventBus);
    }

    public function testShouldUnpublishAccommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0, AccommodationStatus::Published));

        $this->useCase->handle(new UnpublishAccommodationCommand(id: $id));

        $accommodation = $this->repository->findById($id);
        self::assertSame(AccommodationStatus::Draft, $accommodation->getStatus());
    }

    public function testShouldDispatchAccommodationUnpublishedEvent(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0, AccommodationStatus::Published));

        $this->useCase->handle(new UnpublishAccommodationCommand(id: $id));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationUnpublished::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function testShouldNotUnpublishWithUnknownAccommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UnpublishAccommodationCommand(id: $id));
    }
}
