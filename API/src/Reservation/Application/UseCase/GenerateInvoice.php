<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\GenerateInvoiceCommand;
use App\Reservation\Domain\Entity\Invoice;
use App\Reservation\Domain\Entity\InvoiceLine;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Exception\InvoiceNotAvailableException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Reservation\Domain\Port\InvoiceRenderer;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\AccommodationSummaryProvider;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\TeamContactProvider;
use Symfony\Component\Uid\Uuid;

/**
 * Builds the invoice for a paid (confirmed) reservation and renders it to PDF.
 *
 * The reservation holds the frozen price snapshot, so the amounts come straight from
 * it: the stay total plus the platform fee and the solidarity donation it was valued
 * with. Cross-module enrichment (accommodation title/city, host name) is read through
 * Shared provider ports so the module stays isolated.
 */
final readonly class GenerateInvoice
{
    private const string SELLER_NAME = 'ZouriteBnb';
    private const string CURRENCY = 'EUR';

    public function __construct(
        private ReservationRepository $repository,
        private AccommodationSummaryProvider $accommodations,
        private TeamContactProvider $teamContacts,
        private Clock $clock,
        private InvoiceRenderer $renderer,
    ) {
    }

    public function handle(GenerateInvoiceCommand $command): RenderedInvoice
    {
        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($command->reservationId)));

        if (null === $reservation) {
            throw ReservationNotFoundException::becauseId($command->reservationId);
        }

        // An invoice only exists once the stay has been paid, i.e. the reservation
        // is confirmed (payment is captured on confirmation).
        if (ReservationStatus::Confirmed !== $reservation->getStatus()) {
            throw InvoiceNotAvailableException::becauseReservationNotConfirmed($command->reservationId);
        }

        $price = $reservation->getPrice();
        $checkIn = $reservation->getDateRange()->checkIn();
        $checkOut = $reservation->getDateRange()->checkOut();
        $nights = max(1, (int) $checkIn->diff($checkOut)->days);

        $accommodation = $this->accommodations->summaryOf($reservation->getAccommodationId());
        $hostContacts = $this->teamContacts->contactsOf($reservation->getTeamId());
        $hostName = $hostContacts[0]->firstName ?? null;

        $lines = [
            new InvoiceLine(\sprintf('Hébergement (%d nuit%s)', $nights, $nights > 1 ? 's' : ''), $price->totalPrice),
            new InvoiceLine('Frais de service', $price->commissionAmount),
            new InvoiceLine('Don solidaire', $price->donationAmount),
        ];
        $total = round($price->totalPrice + $price->commissionAmount + $price->donationAmount, 2);

        $invoice = new Invoice(
            number: $this->invoiceNumber($command->reservationId),
            issuedAt: $this->clock->now(),
            sellerName: self::SELLER_NAME,
            hostName: $hostName,
            guestName: $reservation->getGuestName()->toString(),
            accommodationTitle: $accommodation?->title,
            accommodationCity: $accommodation?->city,
            checkIn: $checkIn,
            checkOut: $checkOut,
            nights: $nights,
            lines: $lines,
            total: $total,
            currency: self::CURRENCY,
        );

        return new RenderedInvoice(
            filename: \sprintf('facture-%s.pdf', $invoice->number),
            content: $this->renderer->render($invoice),
        );
    }

    private function invoiceNumber(string $reservationId): string
    {
        return 'FAC-'.strtoupper(substr(str_replace('-', '', $reservationId), 0, 10));
    }
}
