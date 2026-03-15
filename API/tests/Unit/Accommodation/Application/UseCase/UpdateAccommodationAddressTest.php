<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationAddress;
use App\Accommodation\Domain\Command\UpdateAccommodationAddressCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationAddressUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidAddressException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationAddressTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationAddress $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationAddress($this->repository, $this->eventBus);
    }

    public function testShouldUpdateAddress(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationAddressCommand(
            id: $id,
            street: '10 Rue de la Paix',
            city: 'Paris',
            zipCode: '75002',
            country: 'France',
        ));

        $accommodation = $this->repository->findById($id);
        self::assertNotNull($accommodation->getAddress());
        self::assertSame('10 Rue de la Paix', $accommodation->getAddress()->street());
        self::assertSame('Paris', $accommodation->getAddress()->city());
        self::assertSame('75002', $accommodation->getAddress()->zipCode());
        self::assertSame('France', $accommodation->getAddress()->country());
    }

    public function testShouldDispatchAddressUpdatedEvent(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationAddressCommand(
            id: $id,
            street: '10 Rue de la Paix',
            city: 'Paris',
            zipCode: '75002',
            country: 'France',
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationAddressUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function testShouldNotUpdateAddressWithUnknownAccommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UpdateAccommodationAddressCommand(
            id: $id,
            street: '10 Rue de la Paix',
            city: 'Paris',
            zipCode: '75002',
            country: 'France',
        ));
    }

    public static function invalidAddressProvider(): \Generator
    {
        yield 'empty street' => [null, 'Paris', '75002', 'France', 'Street is required.'];
        yield 'empty city' => ['10 Rue de la Paix', null, '75002', 'France', 'City is required.'];
        yield 'empty country' => ['10 Rue de la Paix', 'Paris', '75002', null, 'Country is required.'];
    }

    #[DataProvider('invalidAddressProvider')]
    public function testShouldNotUpdateAddressWithInvalidField(
        ?string $street,
        ?string $city,
        ?string $zipCode,
        ?string $country,
        string $expectedMessage,
    ): void {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->useCase->handle(new UpdateAccommodationAddressCommand(
            id: $id,
            street: $street,
            city: $city,
            zipCode: $zipCode,
            country: $country,
        ));
    }
}
