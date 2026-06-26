<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\GenerateInvoice;
use App\Reservation\Domain\Command\GenerateInvoiceCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Exception\InvoiceNotAvailableException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Shared\Domain\Port\AccommodationSummary;
use App\Shared\Domain\Port\UserContact;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryAccommodationSummaryProvider;
use App\Tests\Unit\Notification\Infrastructure\InMemoryTeamContactProvider;
use App\Tests\Unit\Reservation\Infrastructure\CapturingInvoiceRenderer;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GenerateInvoiceTest extends TestCase
{
    private const string RESERVATION_ID = '01961e2f-dead-7000-beef-000000000001';
    private const string ACCOMMODATION_ID = '01961e2f-dead-7000-beef-0000000000a1';
    private const string TEAM_ID = '01961e2f-dead-7000-beef-0000000000b1';

    private InMemoryReservationRepository $repository;
    private InMemoryAccommodationSummaryProvider $accommodations;
    private InMemoryTeamContactProvider $teamContacts;
    private CapturingInvoiceRenderer $renderer;
    private GenerateInvoice $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->accommodations = new InMemoryAccommodationSummaryProvider();
        $this->teamContacts = new InMemoryTeamContactProvider();
        $this->renderer = new CapturingInvoiceRenderer();
        $this->useCase = new GenerateInvoice(
            $this->repository,
            $this->accommodations,
            $this->teamContacts,
            new FixedClock(new \DateTimeImmutable('2026-06-24 10:00:00')),
            $this->renderer,
        );
    }

    public function test_should_render_invoice_with_total_including_fee_and_donation(): void
    {
        $this->seedConfirmedReservation(totalPrice: 400.0, pricePerNight: 100.0);
        $this->accommodations->add(new AccommodationSummary(
            Uuid::fromString(self::ACCOMMODATION_ID),
            'Cosy loft',
            'Paris',
        ));
        $this->teamContacts->addContact(
            Uuid::fromString(self::TEAM_ID),
            new UserContact(Uuid::fromString(self::TEAM_ID), 'host@example.com', 'Alice'),
        );

        $rendered = $this->useCase->handle(new GenerateInvoiceCommand(self::RESERVATION_ID));

        $invoice = $this->renderer->lastInvoice;
        self::assertNotNull($invoice);
        self::assertSame('Cosy loft', $invoice->accommodationTitle);
        self::assertSame('Paris', $invoice->accommodationCity);
        self::assertSame('Alice', $invoice->hostName);
        self::assertSame('John Doe', $invoice->guestName);
        self::assertSame(4, $invoice->nights);
        // Stay 400 + 8% fee (32) + 7% donation (28) = 460 paid.
        self::assertEqualsWithDelta(32.0, $invoice->lines[1]->amount, 0.001);
        self::assertEqualsWithDelta(28.0, $invoice->lines[2]->amount, 0.001);
        self::assertEqualsWithDelta(460.0, $invoice->total, 0.001);
        self::assertStringStartsWith('FAC-', $invoice->number);
        self::assertStringEndsWith('.pdf', $rendered->filename);
        self::assertSame('%PDF-1.4 fake', $rendered->content);
    }

    public function test_should_fail_when_reservation_is_not_confirmed(): void
    {
        $this->seedPendingReservation();

        $this->expectException(InvoiceNotAvailableException::class);
        $this->useCase->handle(new GenerateInvoiceCommand(self::RESERVATION_ID));
    }

    public function test_should_fail_when_reservation_does_not_exist(): void
    {
        $this->expectException(ReservationNotFoundException::class);
        $this->useCase->handle(new GenerateInvoiceCommand(self::RESERVATION_ID));
    }

    private function seedConfirmedReservation(float $totalPrice, float $pricePerNight): void
    {
        $this->repository->save(Reservation::create(
            id: new ReservationId(Uuid::fromString(self::RESERVATION_ID)),
            accommodationId: Uuid::fromString(self::ACCOMMODATION_ID),
            teamId: Uuid::fromString(self::TEAM_ID),
            dateRange: new DateRange(new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-05-05')),
            guestName: new GuestName('John Doe'),
            guestCount: new GuestCount(2),
            price: ReservationPrice::fromStay($totalPrice, $pricePerNight, null),
        ));
    }

    private function seedPendingReservation(): void
    {
        $this->repository->save(Reservation::request(
            id: new ReservationId(Uuid::fromString(self::RESERVATION_ID)),
            accommodationId: Uuid::fromString(self::ACCOMMODATION_ID),
            teamId: Uuid::fromString(self::TEAM_ID),
            dateRange: new DateRange(new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-05-05')),
            guestName: new GuestName('John Doe'),
            guestCount: new GuestCount(2),
            price: ReservationPrice::fromStay(400.0, 100.0, null),
            guestUserId: Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1'),
        ));
    }
}
