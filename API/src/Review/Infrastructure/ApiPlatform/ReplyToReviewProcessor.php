<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Review\Application\UseCase\ReplyToAccommodationReview;
use App\Review\Domain\Command\ReplyToAccommodationReviewCommand;
use App\Review\Domain\Exception\ReviewNotFoundException;
use App\Shared\Domain\Port\TeamMembershipChecker;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<ReplyToReviewInput, void>
 */
final readonly class ReplyToReviewProcessor implements ProcessorInterface
{
    public function __construct(
        private ReplyToAccommodationReview $replyToAccommodationReview,
        private TeamMembershipChecker $teamMembershipChecker,
        private Connection $connection,
        private TransactionalUseCaseHandler $handler,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof ReplyToReviewInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', ReplyToReviewInput::class, get_debug_type($data)));
        }

        $reviewId = (string) $uriVariables['reviewId'];

        $teamId = $this->teamOwningReviewedAccommodation($reviewId);
        if (null === $teamId) {
            throw ReviewNotFoundException::becauseId($reviewId);
        }
        if (!$this->teamMembershipChecker->isMember($this->currentUser->id(), $teamId)) {
            throw new AccessDeniedHttpException('Only a member of the host team can reply to a review.');
        }

        $this->handler->execute(fn () => $this->replyToAccommodationReview->handle(new ReplyToAccommodationReviewCommand(
            reviewId: $reviewId,
            reply: (string) $data->reply,
        )));
    }

    /**
     * Resolves the host team owning the accommodation the review targets, by joining
     * the review to its reservation directly via DBAL — keeping the Review module
     * decoupled from the Reservation/Accommodation modules (vertical-slicing rule).
     */
    private function teamOwningReviewedAccommodation(string $reviewId): ?Uuid
    {
        if (!Uuid::isValid($reviewId)) {
            return null;
        }

        $teamId = $this->connection->fetchOne(
            <<<'SQL'
                SELECT res.team_id
                FROM review rv
                JOIN reservation res ON res.id = rv.reservation_id
                WHERE rv.id = :reviewId AND rv.type = 'accommodation'
                SQL,
            ['reviewId' => Uuid::fromString($reviewId)->toBinary()],
        );

        return false === $teamId ? null : Uuid::fromBinary($teamId);
    }
}
