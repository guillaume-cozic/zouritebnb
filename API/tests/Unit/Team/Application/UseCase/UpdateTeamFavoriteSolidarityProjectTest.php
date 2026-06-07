<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Team\Application\UseCase\UpdateTeamFavoriteSolidarityProject;
use App\Team\Domain\Command\UpdateTeamFavoriteSolidarityProjectCommand;
use App\Team\Domain\Entity\Team;
use App\Team\Domain\Event\TeamFavoriteSolidarityProjectUpdated;
use App\Team\Domain\Exception\TeamNotFoundException;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\Team\Infrastructure\InMemoryTeamRepository;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateTeamFavoriteSolidarityProjectTest extends TestCase
{
    private const string TEAM_ID = '01961e2f-dead-7000-beef-0000000000b1';
    private const string PROJECT_ID = '01961e2f-dead-7000-beef-0000000000c2';

    private InMemoryTeamRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateTeamFavoriteSolidarityProject $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryTeamRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateTeamFavoriteSolidarityProject($this->repository, $this->eventBus);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_set_favorite_solidarity_project_and_dispatch_event(): void
    {
        $teamId = $this->givenTeam();
        $projectId = Uuid::fromString(self::PROJECT_ID);

        $this->useCase->handle(new UpdateTeamFavoriteSolidarityProjectCommand(
            teamId: $teamId,
            favoriteSolidarityProjectId: $projectId,
        ));

        $team = $this->repository->findById($teamId);
        self::assertNotNull($team);
        self::assertNotNull($team->getFavoriteSolidarityProjectId());
        self::assertTrue($projectId->equals($team->getFavoriteSolidarityProjectId()));

        $this->assertFavoriteUpdatedDispatched($teamId);
    }

    public function test_should_clear_favorite_solidarity_project_when_null(): void
    {
        $teamId = $this->givenTeam();
        // First set a project.
        $this->useCase->handle(new UpdateTeamFavoriteSolidarityProjectCommand(
            teamId: $teamId,
            favoriteSolidarityProjectId: Uuid::fromString(self::PROJECT_ID),
        ));
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateTeamFavoriteSolidarityProject($this->repository, $this->eventBus);

        $this->useCase->handle(new UpdateTeamFavoriteSolidarityProjectCommand(
            teamId: $teamId,
            favoriteSolidarityProjectId: null,
        ));

        self::assertNull($this->repository->findById($teamId)?->getFavoriteSolidarityProjectId());
        $this->assertFavoriteUpdatedDispatched($teamId);
    }

    public function test_should_throw_when_team_not_found(): void
    {
        $missingId = Uuid::fromString(self::TEAM_ID);

        $this->expectException(TeamNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Team "%s" not found.', $missingId->toRfc4122()));

        $this->useCase->handle(new UpdateTeamFavoriteSolidarityProjectCommand(
            teamId: $missingId,
            favoriteSolidarityProjectId: Uuid::fromString(self::PROJECT_ID),
        ));
    }

    public function test_should_not_dispatch_event_when_team_not_found(): void
    {
        try {
            $this->useCase->handle(new UpdateTeamFavoriteSolidarityProjectCommand(
                teamId: Uuid::fromString(self::TEAM_ID),
                favoriteSolidarityProjectId: null,
            ));
        } catch (TeamNotFoundException) {
        }

        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    private function givenTeam(): Uuid
    {
        $teamId = Uuid::fromString(self::TEAM_ID);
        $team = new Team($teamId);
        $this->repository->save($team);

        return $teamId;
    }

    private function assertFavoriteUpdatedDispatched(Uuid $teamId): void
    {
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(TeamFavoriteSolidarityProjectUpdated::class, $events[0]);
        self::assertTrue($teamId->equals($events[0]->teamId));
    }
}
