<?php

declare(strict_types=1);

namespace App\Notification\Application\Email;

/**
 * Maps each step of the traveler journey to the Twig view + subject + variables of the
 * email to send. The HTML lives in templates/emails/traveler/*.html.twig; the reference
 * copy is documented in docs/emails-voyageur.md.
 */
final readonly class TravelerEmails
{
    public function welcome(string $greetingName): EmailView
    {
        return new EmailView(
            template: 'emails/traveler/welcome.html.twig',
            subject: 'Bienvenue sur BnB Rodrigues 🌴',
            variables: ['greetingName' => $greetingName],
        );
    }

    public function verifyEmail(string $greetingName, string $verificationUrl): EmailView
    {
        return new EmailView(
            template: 'emails/traveler/verify_email.html.twig',
            subject: 'Confirmez votre adresse email ✉️',
            variables: ['greetingName' => $greetingName, 'verificationUrl' => $verificationUrl],
        );
    }

    public function passwordReset(string $greetingName, string $resetUrl): EmailView
    {
        return new EmailView(
            template: 'emails/traveler/password_reset.html.twig',
            subject: 'Réinitialisation de votre mot de passe 🔑',
            variables: ['greetingName' => $greetingName, 'resetUrl' => $resetUrl],
        );
    }

    public function reservationRequested(
        string $greetingName,
        string $accommodationTitle,
        ?string $city,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): EmailView {
        return new EmailView(
            template: 'emails/traveler/reservation_requested.html.twig',
            subject: \sprintf('Votre demande pour « %s » a bien été envoyée', $accommodationTitle),
            variables: $this->stayVariables($greetingName, $accommodationTitle, $city, $checkIn, $checkOut),
        );
    }

    public function reservationConfirmed(
        string $greetingName,
        string $accommodationTitle,
        ?string $city,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): EmailView {
        return new EmailView(
            template: 'emails/traveler/reservation_confirmed.html.twig',
            subject: \sprintf('C\'est confirmé ! Votre séjour « %s » est réservé 🎉', $accommodationTitle),
            variables: $this->stayVariables($greetingName, $accommodationTitle, $city, $checkIn, $checkOut),
        );
    }

    public function reservationRefused(string $greetingName, string $accommodationTitle): EmailView
    {
        return new EmailView(
            template: 'emails/traveler/reservation_refused.html.twig',
            subject: \sprintf('Votre demande pour « %s » n\'a pas pu être retenue', $accommodationTitle),
            variables: ['greetingName' => $greetingName, 'accommodationTitle' => $accommodationTitle],
        );
    }

    public function reservationExpired(string $greetingName, string $accommodationTitle): EmailView
    {
        return new EmailView(
            template: 'emails/traveler/reservation_expired.html.twig',
            subject: \sprintf('Votre demande pour « %s » a expiré', $accommodationTitle),
            variables: ['greetingName' => $greetingName, 'accommodationTitle' => $accommodationTitle],
        );
    }

    public function reservationCancelled(string $greetingName, string $accommodationTitle): EmailView
    {
        return new EmailView(
            template: 'emails/traveler/reservation_cancelled.html.twig',
            subject: 'Votre réservation a été annulée',
            variables: ['greetingName' => $greetingName, 'accommodationTitle' => $accommodationTitle],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function stayVariables(
        string $greetingName,
        string $accommodationTitle,
        ?string $city,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): array {
        return [
            'greetingName' => $greetingName,
            'accommodationTitle' => $accommodationTitle,
            'city' => $city,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'nights' => (int) $checkIn->diff($checkOut)->format('%a'),
        ];
    }
}
