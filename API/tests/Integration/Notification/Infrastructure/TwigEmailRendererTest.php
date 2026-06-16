<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notification\Infrastructure;

use App\Notification\Domain\Port\EmailRenderer;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;

final class TwigEmailRendererTest extends RepositoryTestCase
{
    private EmailRenderer $renderer;

    #[Before]
    public function initRenderer(): void
    {
        $this->renderer = self::getContainer()->get(EmailRenderer::class);
    }

    public function test_should_render_the_traveler_welcome_view(): void
    {
        $html = $this->renderer->renderHtml('emails/traveler/welcome.html.twig', ['greetingName' => 'Marie']);

        self::assertStringContainsString('BnB Rodrigues', $html);
        self::assertStringContainsString('Bienvenue', $html);
        self::assertStringContainsString('Bonjour Marie,', $html);
    }

    public function test_should_render_the_traveler_request_view_with_dates_and_nights(): void
    {
        $html = $this->renderer->renderHtml('emails/traveler/reservation_requested.html.twig', [
            'greetingName' => 'Marie',
            'accommodationTitle' => 'Villa Corail',
            'city' => 'Port Mathurin',
            'checkIn' => new \DateTimeImmutable('2026-07-10'),
            'checkOut' => new \DateTimeImmutable('2026-07-17'),
            'nights' => 7,
        ]);

        self::assertStringContainsString('Villa Corail', $html);
        self::assertStringContainsString('Port Mathurin', $html);
        self::assertStringContainsString('10/07/2026', $html);
        self::assertStringContainsString('17/07/2026', $html);
        self::assertStringContainsString('7 nuits', $html);
    }

    public function test_should_render_the_host_request_view(): void
    {
        $html = $this->renderer->renderHtml('emails/host/reservation_requested.html.twig', [
            'greetingName' => 'Jean',
            'guestName' => 'Marie Dupont',
            'accommodationTitle' => 'Villa Corail',
            'city' => null,
            'checkIn' => new \DateTimeImmutable('2026-07-10'),
            'checkOut' => new \DateTimeImmutable('2026-07-11'),
            'nights' => 1,
        ]);

        self::assertStringContainsString('Nouvelle demande de réservation', $html);
        self::assertStringContainsString('Marie Dupont', $html);
        self::assertStringContainsString('1 nuit', $html);
        self::assertStringNotContainsString('1 nuits', $html);
    }

    public function test_should_render_the_message_posted_view(): void
    {
        $html = $this->renderer->renderHtml('emails/message_posted.html.twig', [
            'greetingName' => 'Jean',
            'senderName' => 'Marie',
            'accommodationTitle' => 'Villa Corail',
            'messageBody' => 'Bonjour, une question sur le parking ?',
        ]);

        self::assertStringContainsString('Nouveau message', $html);
        self::assertStringContainsString('Marie', $html);
        self::assertStringContainsString('Villa Corail', $html);
        self::assertStringContainsString('parking', $html);
    }

    public function test_should_render_the_cohost_invitation_view(): void
    {
        $html = $this->renderer->renderHtml('emails/host/cohost_invitation.html.twig', [
            'invitedEmail' => 'newcohost@example.com',
        ]);

        self::assertStringContainsString('co-hôte', $html);
        self::assertStringContainsString('newcohost@example.com', $html);
    }
}
