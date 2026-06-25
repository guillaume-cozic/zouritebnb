<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationCancellationPolicy;
use App\Accommodation\Domain\Command\UpdateAccommodationCancellationPolicyCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\CancellationPolicy;
use App\Accommodation\Domain\Event\AccommodationCancellationPolicyUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidCancellationPolicyException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationCancellationPolicyTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationCancellationPolicy $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationCancellationPolicy($this->repository, $this->eventBus);
    }

    public function test_should_default_to_flexible(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $accommodation = $this->repository->findById($id);
        self::assertSame(CancellationPolicy::Flexible, $accommodation->getCancellationPolicy());
    }

    public function test_should_update_to_moderate(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationCancellationPolicyCommand(accommodationId: $id, cancellationPolicy: 'moderate'));

        $accommodation = $this->repository->findById($id);
        self::assertSame(CancellationPolicy::Moderate, $accommodation->getCancellationPolicy());
    }

    public function test_should_dispatch_cancellation_policy_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationCancellationPolicyCommand(accommodationId: $id, cancellationPolicy: 'moderate'));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationCancellationPolicyUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_reject_unknown_policy(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidCancellationPolicyException::class);

        $this->useCase->handle(new UpdateAccommodationCancellationPolicyCommand(accommodationId: $id, cancellationPolicy: 'strict'));
    }

    public function test_should_not_update_with_unknown_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationCancellationPolicyCommand(accommodationId: $id, cancellationPolicy: 'flexible'));
    }
}
