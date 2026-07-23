<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationExtraServices;
use App\Accommodation\Domain\Command\UpdateAccommodationExtraServicesCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationExtraServicesUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidExtraServiceException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationExtraServicesTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationExtraServices $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationExtraServices($this->repository, $this->eventBus);
    }

    private function givenAccommodation(Uuid $id): void
    {
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
    }

    public function test_should_replace_extra_services(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $services = [
            ['name' => 'Ménage de fin de séjour', 'price' => 60.0, 'billedWithReservation' => true],
            ['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false],
        ];
        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, $services));

        $accommodation = $this->repository->findById($id);
        self::assertSame($services, $accommodation->getExtraServices()->toArray());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationExtraServicesUpdated::class, $events[0]);
    }

    public function test_should_clear_extra_services_with_empty_list(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);
        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, [['name' => 'Ménage', 'price' => 60.0]]));

        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, []));

        self::assertTrue($this->repository->findById($id)->getExtraServices()->isEmpty());
    }

    public function test_should_trim_extra_service_name(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, [['name' => '  Ménage  ', 'price' => 60.0]]));

        self::assertSame([['name' => 'Ménage', 'price' => 60.0, 'billedWithReservation' => false]], $this->repository->findById($id)->getExtraServices()->toArray());
    }

    public function test_should_default_billed_with_reservation_to_false_when_key_absent(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, [['name' => 'Petit-déjeuner', 'price' => 12.5]]));

        self::assertSame(
            [['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false]],
            $this->repository->findById($id)->getExtraServices()->toArray(),
        );
    }

    public function test_should_sum_prices_of_services_billed_with_reservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, [
            ['name' => 'Ménage', 'price' => 30.0, 'billedWithReservation' => true],
            ['name' => 'Linge de maison', 'price' => 15.5, 'billedWithReservation' => true],
            ['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false],
        ]));

        self::assertSame(45.5, $this->repository->findById($id)->getExtraServices()->billedWithReservationTotal());
    }

    public function test_should_return_zero_billed_with_reservation_total_when_no_service_is_billed(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, [
            ['name' => 'Petit-déjeuner', 'price' => 12.5],
        ]));

        self::assertSame(0.0, $this->repository->findById($id)->getExtraServices()->billedWithReservationTotal());
    }

    #[DataProvider('invalidExtraServices')]
    public function test_should_reject_invalid_extra_service(array $service, string $expectedMessage): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenAccommodation($id);

        $this->expectException(InvalidExtraServiceException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand($id, [$service]));
    }

    public static function invalidExtraServices(): \Generator
    {
        yield 'empty name' => [
            ['name' => '', 'price' => 20.0],
            'Extra service name must not be empty.',
        ];
        yield 'blank name' => [
            ['name' => '   ', 'price' => 20.0],
            'Extra service name must not be empty.',
        ];
        yield 'name too long' => [
            ['name' => str_repeat('a', 101), 'price' => 20.0],
            'Extra service name must not exceed 100 characters, got 101.',
        ];
        yield 'zero price' => [
            ['name' => 'Ménage', 'price' => 0.0],
            'Extra service price must be strictly positive, got 0.',
        ];
        yield 'negative price' => [
            ['name' => 'Ménage', 'price' => -5.0],
            'Extra service price must be strictly positive, got -5.',
        ];
        yield 'non-boolean billedWithReservation' => [
            ['name' => 'Ménage', 'price' => 30.0, 'billedWithReservation' => 'yes'],
            'Extra service billedWithReservation must be a boolean.',
        ];
    }

    public function test_should_throw_when_accommodation_missing(): void
    {
        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationExtraServicesCommand(Uuid::fromString('01961e2f-dead-7000-beef-0000000000ee'), []));
    }
}
