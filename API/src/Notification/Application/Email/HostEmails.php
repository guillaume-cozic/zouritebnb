<?php

declare(strict_types=1);

namespace App\Notification\Application\Email;

/**
 * Maps each host-facing event to the Twig view + subject + variables of the email to send.
 * The HTML lives in templates/emails/host/*.html.twig; the reference copy is documented in
 * docs/emails-hote.md.
 */
final readonly class HostEmails
{
    public function reservationRequested(
        string $greetingName,
        string $guestName,
        string $accommodationTitle,
        ?string $city,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): EmailView {
        return new EmailView(
            template: 'emails/host/reservation_requested.html.twig',
            subject: \sprintf('Nouvelle demande de réservation pour « %s »', $accommodationTitle),
            variables: [
                'greetingName' => $greetingName,
                'guestName' => $guestName,
                'accommodationTitle' => $accommodationTitle,
                'city' => $city,
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
                'nights' => (int) $checkIn->diff($checkOut)->format('%a'),
            ],
        );
    }

    public function reservationCancelled(
        string $greetingName,
        string $guestName,
        string $accommodationTitle,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): EmailView {
        return new EmailView(
            template: 'emails/host/reservation_cancelled.html.twig',
            subject: \sprintf('Réservation annulée — « %s »', $accommodationTitle),
            variables: [
                'greetingName' => $greetingName,
                'guestName' => $guestName,
                'accommodationTitle' => $accommodationTitle,
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
            ],
        );
    }

    public function coHostInvitation(string $invitedEmail): EmailView
    {
        return new EmailView(
            template: 'emails/host/cohost_invitation.html.twig',
            subject: 'Vous êtes invité(e) comme co-hôte sur BnB Rodrigues',
            variables: ['invitedEmail' => $invitedEmail],
        );
    }
}
