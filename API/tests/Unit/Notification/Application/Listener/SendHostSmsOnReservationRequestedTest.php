<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Listener;

use App\Notification\Application\Listener\HostReservationEmailContextResolver;
use App\Notification\Application\Listener\SendHostSmsOnReservationRequested;
use App\Notification\Application\Sms\HostSms;
use App\Notification\Application\UseCase\QueueSms;
use App\Shared\Domain\Event\ReservationRequested;
use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\ReservationSummary;
use App\Shared\Domain\Port\UserContact;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryAccommodationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryReservationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemorySmsOutbox;
use App\Tests\Unit\Notification\Infrastructure\InMemoryTeamContactProvider;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendHostSmsOnReservationRequestedTest extends TestCase
{
    private InMemoryReservationSummaryProvider $reservations;
    private InMemoryAccommodationSummaryProvider $accommodations;
    private InMemoryTeamContactProvider $teamContacts;
    private InMemorySmsOutbox $outbox;
    private SendHostSmsOnReservationRequested $listener;

    private Uuid $reservationId;
    private Uuid $accommodationId;
    private Uuid $teamId;

    #[Before]
    public function initListener(): void
    {
        $this->reservations = new InMemoryReservationSummaryProvider();
        $this->accommodations = new InMemoryAccommodationSummaryProvider();
        $this->teamContacts = new InMemoryTeamContactProvider();
        $this->outbox = new InMemorySmsOutbox();

        $this->listener = new SendHostSmsOnReservationRequested(
            new HostReservationEmailContextResolver($this->reservations, $this->accommodations, $this->teamContacts),
            new HostSms(),
            new QueueSms($this->outbox, new FixedClock(new \DateTimeImmutable('2026-06-17 09:00:00'))),
        );

        $this->reservationId = Uuid::fromString('01961e2f-beef-7000-dead-000000000001');
        $this->accommodationId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000b1');
        $this->teamId = Uuid::fromString('01961e2f-beef-7000-dead-0000000000d1');

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
    }

    public function test_should_text_hosts_who_have_a_phone_number(): void
    {
        $this->teamContacts->addContact($this->teamId, new UserContact(Uuid::v7(), 'host@example.com', 'Jean', '+230 5 765 4321'));

        ($this->listener)(new ReservationRequested($this->reservationId, Uuid::v7()));

        $queued = $this->outbox->all();
        self::assertCount(1, $queued);
        self::assertSame('+230 5 765 4321', $queued[0]->getRecipient()->toString());
        self::assertStringContainsString('Marie Dupont', $queued[0]->getText());
        self::assertStringContainsString('Villa Corail', $queued[0]->getText());
        self::assertStringContainsString('10/07/2026', $queued[0]->getText());
    }

    public function test_should_skip_hosts_without_a_phone_number(): void
    {
        $this->teamContacts->addContact($this->teamId, new UserContact(Uuid::v7(), 'host@example.com', 'Jean'));

        ($this->listener)(new ReservationRequested($this->reservationId, Uuid::v7()));

        self::assertCount(0, $this->outbox->all());
    }
}
