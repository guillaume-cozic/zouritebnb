<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Doctrine;

use App\Review\Domain\Port\CompletedStay;
use App\Review\Domain\Port\CompletedStayChecker;
use App\Shared\Domain\Port\Clock;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Confirms a completed stay by querying the reservation table directly via DBAL.
 *
 * A stay is "completed" when a reservation between the given guest and accommodation
 * is confirmed and its checkout date is in the past. Querying the table through DBAL
 * (rather than the Reservation repository) keeps the Review module decoupled from the
 * Reservation module, as required by the vertical-slicing architecture rules.
 */
final readonly class DbalCompletedStayChecker implements CompletedStayChecker
{
    private const string CONFIRMED_STATUS = 'confirmed';

    public function __construct(
        private Connection $connection,
        private Clock $clock,
    ) {
    }

    public function hasCompletedStay(Uuid $guestUserId, Uuid $accommodationId): bool
    {
        return null !== $this->findCompletedStay($guestUserId, $accommodationId);
    }

    public function findCompletedStay(Uuid $guestUserId, Uuid $accommodationId): ?CompletedStay
    {
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT id, accommodation_id, guest_user_id
                FROM reservation
                WHERE guest_user_id = :guestUserId
                  AND accommodation_id = :accommodationId
                  AND status = :status
                  AND check_out < :now
                ORDER BY check_out DESC
                LIMIT 1
                SQL,
            [
                'guestUserId' => $guestUserId->toBinary(),
                'accommodationId' => $accommodationId->toBinary(),
                'status' => self::CONFIRMED_STATUS,
                'now' => $this->clock->now()->format('Y-m-d H:i:s'),
            ],
        );

        if (false === $row) {
            return null;
        }

        return new CompletedStay(
            reservationId: Uuid::fromBinary($row['id']),
            accommodationId: Uuid::fromBinary($row['accommodation_id']),
            guestUserId: Uuid::fromBinary($row['guest_user_id']),
        );
    }
}
