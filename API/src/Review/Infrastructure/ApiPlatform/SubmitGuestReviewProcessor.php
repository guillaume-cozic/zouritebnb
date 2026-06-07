<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Review\Application\UseCase\SubmitGuestReview;
use App\Review\Domain\Command\SubmitGuestReviewCommand;
use App\Shared\Domain\Port\TeamMembershipChecker;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<SubmitGuestReviewInput, void>
 */
final readonly class SubmitGuestReviewProcessor implements ProcessorInterface
{
    public function __construct(
        private SubmitGuestReview $submitGuestReview,
        private TeamMembershipChecker $teamMembershipChecker,
        private Connection $connection,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof SubmitGuestReviewInput);

        if ('' === $data->authorUserId) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication is required to submit a review.');
        }

        $authorUserId = Uuid::fromString($data->authorUserId);
        $accommodationId = Uuid::fromString($data->accommodationId);

        $teamId = $this->teamOwningAccommodation($accommodationId);
        if (null === $teamId || !$this->teamMembershipChecker->isMember($authorUserId, $teamId)) {
            throw new AccessDeniedHttpException('Only a member of the host team can review a guest.');
        }

        $this->handler->execute(fn () => $this->submitGuestReview->handle(new SubmitGuestReviewCommand(
            authorUserId: $authorUserId,
            accommodationId: $accommodationId,
            guestUserId: Uuid::fromString($data->guestUserId),
            rating: $data->rating,
            comment: $data->comment,
        )));
    }

    /**
     * Resolves the host team owning the accommodation by reading the reservation table directly via DBAL.
     *
     * Querying the table (rather than the Accommodation/Reservation modules) keeps the Review module
     * decoupled from the other modules, as required by the vertical-slicing architecture rules.
     */
    private function teamOwningAccommodation(Uuid $accommodationId): ?Uuid
    {
        $teamId = $this->connection->fetchOne(
            'SELECT team_id FROM reservation WHERE accommodation_id = :accommodationId LIMIT 1',
            ['accommodationId' => $accommodationId->toBinary()],
        );

        if (false === $teamId) {
            return null;
        }

        return Uuid::fromBinary($teamId);
    }
}
