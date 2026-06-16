<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Listener;

use App\Notification\Application\Email\HostEmails;
use App\Notification\Application\Listener\HostReservationEmailContextResolver;
use App\Notification\Application\Listener\SendHostRequestEmailOnReservationRequested;
use App\Notification\Application\UseCase\QueueEmail;
use App\Shared\Domain\Event\ReservationRequested;
use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\ReservationSummary;
use App\Shared\Domain\Port\UserContact;
use App\Tests\Unit\Notification\Infrastructure\FakeEmailRenderer;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryAccommodationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use App\Tests\Unit\Notification\Infrastructure\InMemoryReservationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryTeamContactProvider;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendHostRequestEmailOnReservationRequestedTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private InMemoryReservationSummaryProvider $reservations;
    private InMemoryAccommodationSummaryProvider $accommodations;
    private InMemoryTeamContactProvider $teamContacts;
    private SendHostRequestEmailOnReservationRequested $listener;

    private Uuid $reservationId;
    private Uuid $accommodationId;
    private Uuid $teamId;

    #[Before]
    public function initListener(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->reservations = new InMemoryReservationSummaryProvider();
        $this->accommodations = new InMemoryAccommodationSummaryProvider();
        $this->teamContacts = new InMemoryTeamContactProvider();

        $this->listener = new SendHostRequestEmailOnReservationRequested(
            new HostReservationEmailContextResolver($this->reservations, $this->accommodations, $this->teamContacts),
            new HostEmails(),
            new QueueEmail($this->outbox, new FakeEmailRenderer(), new FixedClock(new \DateTimeImmutable('2026-06-16 09:00:00'))),
        );

        $this->reservationId = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        $this->accommodationId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000b1');
        $this->teamId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000d1');
    }

    public function test_should_notify_every_host_of_the_team(): void
    {
        $this->reservations->add(new ReservationSummary(
            reservationId: $this->reservationId,
            accommodationId: $this->accommodationId,
            teamId: $this->teamId,
            guestUserId: Uuid::fromString('01961e2f-beef-7000-dead-0000000000c1'),
            guestName: 'Marie Dupont',
            checkIn: new \DateTimeImmutable('2026-07-10'),
            checkOut: new \DateTimeImmutable('2026-07-17'),
        ));
        $this->accommodations->add(new AccommodationSummary($this->accommodationId, 'Villa Corail', 'Port Mathurin'));
        $this->teamContacts->addContact($this->teamId, new UserContact(Uuid::v7(), 'host@example.com', 'Jean'));
        $this->teamContacts->addContact($this->teamId, new UserContact(Uuid::v7(), 'cohost@example.com', 'Paul'));

        ($this->listener)(new ReservationRequested($this->reservationId, Uuid::v7()));

        $queued = $this->outbox->all();
        self::assertCount(2, $queued);
        $recipients = array_map(static fn ($e) => $e->getRecipient()->toString(), $queued);
        self::assertContains('host@example.com', $recipients);
        self::assertContains('cohost@example.com', $recipients);
        self::assertStringContainsString('Villa Corail', $queued[0]->getSubject());
        self::assertStringContainsString('Marie Dupont', $queued[0]->getHtmlBody());
        self::assertStringContainsString('emails/host/reservation_requested.html.twig', $queued[0]->getHtmlBody());
    }

    public function test_should_do_nothing_when_the_team_has_no_contact(): void
    {
        $this->reservations->add(new ReservationSummary(
            reservationId: $this->reservationId,
            accommodationId: $this->accommodationId,
            teamId: $this->teamId,
            guestUserId: null,
            guestName: 'Marie Dupont',
            checkIn: new \DateTimeImmutable('2026-07-10'),
            checkOut: new \DateTimeImmutable('2026-07-17'),
        ));
        $this->accommodations->add(new AccommodationSummary($this->accommodationId, 'Villa Corail', 'Port Mathurin'));

        ($this->listener)(new ReservationRequested($this->reservationId, Uuid::v7()));

        self::assertCount(0, $this->outbox->all());
    }
}
