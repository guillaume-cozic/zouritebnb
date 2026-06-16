<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Listener;

use App\Notification\Application\Email\TravelerEmails;
use App\Notification\Application\Listener\ReservationEmailContextResolver;
use App\Notification\Application\Listener\SendConfirmationEmailOnReservationConfirmed;
use App\Notification\Application\UseCase\QueueEmail;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\ReservationSummary;
use App\Shared\Domain\Port\UserContact;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryAccommodationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use App\Tests\Unit\Notification\Infrastructure\InMemoryReservationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryUserContactProvider;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendConfirmationEmailOnReservationConfirmedTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private InMemoryReservationSummaryProvider $reservations;
    private InMemoryAccommodationSummaryProvider $accommodations;
    private InMemoryUserContactProvider $contacts;
    private SendConfirmationEmailOnReservationConfirmed $listener;

    private Uuid $reservationId;
    private Uuid $accommodationId;
    private Uuid $guestUserId;

    #[Before]
    public function initListener(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->reservations = new InMemoryReservationSummaryProvider();
        $this->accommodations = new InMemoryAccommodationSummaryProvider();
        $this->contacts = new InMemoryUserContactProvider();

        $this->listener = new SendConfirmationEmailOnReservationConfirmed(
            new ReservationEmailContextResolver($this->reservations, $this->accommodations, $this->contacts),
            new TravelerEmails(),
            new QueueEmail($this->outbox, new FixedClock(new \DateTimeImmutable('2026-06-16 09:00:00'))),
        );

        $this->reservationId = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        $this->accommodationId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000b1');
        $this->guestUserId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000c1');
    }

    public function test_should_queue_a_confirmation_email_for_the_guest(): void
    {
        $this->reservations->add(new ReservationSummary(
            reservationId: $this->reservationId,
            accommodationId: $this->accommodationId,
            teamId: Uuid::fromString('01961e2f-beef-7000-dead-0000000000d1'),
            guestUserId: $this->guestUserId,
            guestName: 'Marie Dupont',
            checkIn: new \DateTimeImmutable('2026-07-10'),
            checkOut: new \DateTimeImmutable('2026-07-17'),
        ));
        $this->accommodations->add(new AccommodationSummary($this->accommodationId, 'Villa Corail', 'Port Mathurin'));
        $this->contacts->add(new UserContact($this->guestUserId, 'marie@example.com', 'Marie'));

        ($this->listener)(new ReservationConfirmed($this->reservationId));

        $queued = $this->outbox->all();
        self::assertCount(1, $queued);
        self::assertSame('marie@example.com', $queued[0]->getRecipient()->toString());
        self::assertStringContainsString('Villa Corail', $queued[0]->getHtmlBody());
        self::assertStringContainsString('confirmé', $queued[0]->getSubject());
    }

    public function test_should_skip_back_office_reservations_without_a_guest_account(): void
    {
        $this->reservations->add(new ReservationSummary(
            reservationId: $this->reservationId,
            accommodationId: $this->accommodationId,
            teamId: Uuid::fromString('01961e2f-beef-7000-dead-0000000000d1'),
            guestUserId: null,
            guestName: 'Walk-in guest',
            checkIn: new \DateTimeImmutable('2026-07-10'),
            checkOut: new \DateTimeImmutable('2026-07-17'),
        ));
        $this->accommodations->add(new AccommodationSummary($this->accommodationId, 'Villa Corail', 'Port Mathurin'));

        ($this->listener)(new ReservationConfirmed($this->reservationId));

        self::assertCount(0, $this->outbox->all());
    }
}
