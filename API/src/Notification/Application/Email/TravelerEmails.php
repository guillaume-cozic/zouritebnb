<?php

declare(strict_types=1);

namespace App\Notification\Application\Email;

/**
 * Builds the French copy of the transactional emails sent to a traveler at the
 * different steps of their journey. Pure PHP (no templating engine) so it stays in the
 * framework-agnostic application layer and is trivially unit-testable. The reference
 * copy lives in docs/emails-voyageur.md.
 */
final readonly class TravelerEmails
{
    public function welcome(string $greetingName): RenderedEmail
    {
        $body = $this->paragraph(\sprintf('Bonjour %s,', $this->escape($greetingName)))
            .$this->paragraph('Bienvenue sur <strong>BnB Rodrigues</strong> ! Votre compte est désormais actif.')
            .$this->paragraph('Ici, chaque séjour réservé soutient un projet solidaire local — voyager rime avec impact positif.')
            .$this->paragraph('Découvrez les hébergements de l\'île et trouvez votre prochain coup de cœur.');

        return new RenderedEmail(
            subject: 'Bienvenue sur BnB Rodrigues 🌴',
            htmlBody: $this->layout('Bienvenue 🌴', $body),
        );
    }

    public function reservationRequested(
        string $greetingName,
        string $accommodationTitle,
        ?string $city,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): RenderedEmail {
        $body = $this->paragraph(\sprintf('Bonjour %s,', $this->escape($greetingName)))
            .$this->paragraph(\sprintf(
                'Votre <strong>demande de réservation</strong> pour « %s »%s a bien été transmise à votre hôte. 🙌',
                $this->escape($accommodationTitle),
                $this->city(', '.$city),
            ))
            .$this->paragraph($this->stayLine($checkIn, $checkOut))
            .$this->paragraph('Votre hôte dispose de <strong>24 heures</strong> pour répondre. <strong>Aucun montant ne sera prélevé tant que votre hôte n\'a pas accepté</strong> ; à défaut, l\'autorisation de paiement est automatiquement libérée.');

        return new RenderedEmail(
            subject: \sprintf('Votre demande pour « %s » a bien été envoyée', $accommodationTitle),
            htmlBody: $this->layout('Demande envoyée', $body),
        );
    }

    public function reservationConfirmed(
        string $greetingName,
        string $accommodationTitle,
        ?string $city,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): RenderedEmail {
        $body = $this->paragraph(\sprintf('Bonjour %s,', $this->escape($greetingName)))
            .$this->paragraph(\sprintf(
                'Excellente nouvelle : votre hôte a <strong>accepté votre réservation</strong> pour « %s »%s ! 🎉',
                $this->escape($accommodationTitle),
                $this->city(', '.$city),
            ))
            .$this->paragraph($this->stayLine($checkIn, $checkOut))
            .$this->paragraph('Votre séjour est confirmé et votre paiement a été encaissé. Votre hôte vous communiquera les détails pratiques (arrivée, clés, accès) via la messagerie.')
            .$this->paragraph('Merci d\'avoir voyagé solidaire — votre séjour soutient un projet local de Rodrigues. 💚');

        return new RenderedEmail(
            subject: \sprintf('C\'est confirmé ! Votre séjour « %s » est réservé 🎉', $accommodationTitle),
            htmlBody: $this->layout('Réservation confirmée 🎉', $body),
        );
    }

    public function reservationRefused(string $greetingName, string $accommodationTitle): RenderedEmail
    {
        $body = $this->paragraph(\sprintf('Bonjour %s,', $this->escape($greetingName)))
            .$this->paragraph(\sprintf(
                'Malheureusement, votre hôte n\'a pas pu accepter votre demande pour « %s ».',
                $this->escape($accommodationTitle),
            ))
            .$this->paragraph('Pas d\'inquiétude : <strong>aucun montant n\'a été prélevé</strong> et l\'autorisation de paiement a été libérée.')
            .$this->paragraph('Rodrigues regorge d\'autres hébergements qui pourraient vous séduire.');

        return new RenderedEmail(
            subject: \sprintf('Votre demande pour « %s » n\'a pas pu être retenue', $accommodationTitle),
            htmlBody: $this->layout('Demande non retenue', $body),
        );
    }

    public function reservationExpired(string $greetingName, string $accommodationTitle): RenderedEmail
    {
        $body = $this->paragraph(\sprintf('Bonjour %s,', $this->escape($greetingName)))
            .$this->paragraph(\sprintf(
                'Votre hôte n\'a pas répondu dans le délai de <strong>24 heures</strong>, votre demande pour « %s » a donc expiré.',
                $this->escape($accommodationTitle),
            ))
            .$this->paragraph('<strong>Aucun montant n\'a été prélevé.</strong> Vous pouvez renouveler votre demande ou explorer d\'autres hébergements disponibles aux mêmes dates.');

        return new RenderedEmail(
            subject: \sprintf('Votre demande pour « %s » a expiré', $accommodationTitle),
            htmlBody: $this->layout('Demande expirée', $body),
        );
    }

    public function reservationCancelled(string $greetingName, string $accommodationTitle): RenderedEmail
    {
        $body = $this->paragraph(\sprintf('Bonjour %s,', $this->escape($greetingName)))
            .$this->paragraph(\sprintf(
                'Votre réservation pour « %s » a été <strong>annulée</strong>.',
                $this->escape($accommodationTitle),
            ))
            .$this->paragraph('Si un paiement avait été encaissé, le remboursement est traité selon les conditions d\'annulation ; vous recevrez le cas échéant une confirmation séparée.')
            .$this->paragraph('Nous espérons vous accueillir bientôt pour un prochain séjour.');

        return new RenderedEmail(
            subject: 'Votre réservation a été annulée',
            htmlBody: $this->layout('Réservation annulée', $body),
        );
    }

    private function stayLine(\DateTimeImmutable $checkIn, \DateTimeImmutable $checkOut): string
    {
        $nights = (int) $checkIn->diff($checkOut)->format('%a');

        return \sprintf(
            '📅 Du <strong>%s</strong> au <strong>%s</strong> (%d nuit%s).',
            $checkIn->format('d/m/Y'),
            $checkOut->format('d/m/Y'),
            $nights,
            $nights > 1 ? 's' : '',
        );
    }

    private function city(string $value): string
    {
        $trimmed = trim($value, ', ');

        return '' === $trimmed ? '' : ', '.$this->escape($trimmed);
    }

    private function paragraph(string $html): string
    {
        return \sprintf('<p style="margin:0 0 16px;line-height:1.6;color:#374151;">%s</p>', $html);
    }

    private function layout(string $title, string $bodyHtml): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
            <body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr><td align="center">
                  <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr><td style="background:#1d4ed8;padding:24px 32px;color:#ffffff;font-size:20px;font-weight:bold;">BnB Rodrigues 🌴</td></tr>
                    <tr><td style="padding:32px;">
                      <h1 style="margin:0 0 20px;font-size:22px;color:#111827;">{$title}</h1>
                      {$bodyHtml}
                      <p style="margin:24px 0 0;color:#6b7280;">L'équipe BnB Rodrigues 🌴</p>
                    </td></tr>
                    <tr><td style="padding:16px 32px;background:#f9fafb;color:#9ca3af;font-size:12px;">Vous recevez cet email car vous avez un compte sur BnB Rodrigues.</td></tr>
                  </table>
                </td></tr>
              </table>
            </body>
            </html>
            HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES, 'UTF-8');
    }
}
