<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Http;

use App\Reservation\Application\UseCase\GenerateInvoice;
use App\Reservation\Domain\Command\GenerateInvoiceCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Exception\InvoiceNotAvailableException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Reservation\Infrastructure\Security\ReservationAccessGuard;
use App\Shared\Infrastructure\Security\CurrentUser;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Streams the PDF invoice of a paid reservation as an attachment. Only the guest
 * who booked or a member of the host team may download it.
 */
#[AsController]
final readonly class DownloadInvoiceController
{
    public function __construct(
        private ReservationRepository $repository,
        private ReservationAccessGuard $accessGuard,
        private CurrentUser $currentUser,
        private GenerateInvoice $generateInvoice,
    ) {
    }

    #[Route('/api/reservations/{reservationId}/invoice', name: 'reservation_invoice', methods: ['GET'])]
    public function __invoke(string $reservationId): Response
    {
        try {
            $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($reservationId)));
        } catch (\InvalidArgumentException) {
            throw new NotFoundHttpException();
        }

        if (null === $reservation) {
            throw new NotFoundHttpException();
        }

        // Throws 403 unless the current user is the guest or a host of the reservation.
        $this->accessGuard->assertHostOrGuest($reservation, $this->currentUser);

        try {
            $invoice = $this->generateInvoice->handle(new GenerateInvoiceCommand($reservationId));
        } catch (ReservationNotFoundException|InvoiceNotAvailableException) {
            throw new NotFoundHttpException();
        }

        return new Response(
            $invoice->content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $invoice->filename,
                ),
            ],
        );
    }
}
