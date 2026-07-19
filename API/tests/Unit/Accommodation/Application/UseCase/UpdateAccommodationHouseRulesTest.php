<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationHouseRules;
use App\Accommodation\Domain\Command\UpdateAccommodationHouseRulesCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationHouseRulesUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidHouseRulesException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationHouseRulesTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationHouseRules $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationHouseRules($this->repository, $this->eventBus);
    }

    public function test_should_set_house_rules(): void
    {
        $id = $this->givenAccommodation();

        $this->useCase->handle(new UpdateAccommodationHouseRulesCommand(
            $id,
            smokingAllowed: false,
            petsAllowed: true,
            partiesAllowed: false,
            houseRulesNotes: 'Merci de retirer vos chaussures.',
        ));

        $accommodation = $this->repository->findById($id);
        self::assertFalse($accommodation->isSmokingAllowed());
        self::assertTrue($accommodation->isPetsAllowed());
        self::assertFalse($accommodation->isPartiesAllowed());
        self::assertSame('Merci de retirer vos chaussures.', $accommodation->getHouseRulesNotes());
    }

    public function test_should_normalize_blank_notes_to_null(): void
    {
        $id = $this->givenAccommodation();

        $this->useCase->handle(new UpdateAccommodationHouseRulesCommand(
            $id,
            smokingAllowed: true,
            petsAllowed: false,
            partiesAllowed: false,
            houseRulesNotes: '   ',
        ));

        self::assertNull($this->repository->findById($id)->getHouseRulesNotes());
    }

    public function test_should_dispatch_event(): void
    {
        $id = $this->givenAccommodation();

        $this->useCase->handle(new UpdateAccommodationHouseRulesCommand(
            $id,
            smokingAllowed: false,
            petsAllowed: false,
            partiesAllowed: true,
            houseRulesNotes: null,
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationHouseRulesUpdated::class, $events[0]);
    }

    public function test_should_reject_notes_longer_than_max_length(): void
    {
        $id = $this->givenAccommodation();

        $this->expectException(InvalidHouseRulesException::class);

        $this->useCase->handle(new UpdateAccommodationHouseRulesCommand(
            $id,
            smokingAllowed: false,
            petsAllowed: false,
            partiesAllowed: false,
            houseRulesNotes: str_repeat('a', Accommodation::HOUSE_RULES_NOTES_MAX_LENGTH + 1),
        ));
    }

    public function test_should_throw_when_accommodation_unknown(): void
    {
        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationHouseRulesCommand(
            Uuid::fromString('01961e2f-dead-7000-beef-000000000099'),
            smokingAllowed: false,
            petsAllowed: true,
            partiesAllowed: false,
            houseRulesNotes: null,
        ));
    }

    private function givenAccommodation(): Uuid
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        return $id;
    }
}
