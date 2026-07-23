<?php

declare(strict_types=1);

namespace App\Tests\E2e\Contact;

use PHPUnit\Framework\Attributes\DataProvider;

final class SendContactMessageTest extends ContactApiTestCase
{
    public function test_should_send_contact_message_without_authentication(): void
    {
        // No Authorization header: the contact form is public, no account is required.
        self::createClient()->request('POST', '/api/contact_messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'Marie Dupont',
                'email' => 'marie.dupont@example.com',
                'subject' => 'Question sur une réservation',
                'message' => 'Bonjour, est-il possible de modifier les dates de ma réservation ?',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);

        $contactMessage = $this->findContactMessage('marie.dupont@example.com');
        self::assertNotNull($contactMessage);
        self::assertSame('Marie Dupont', $contactMessage->getName());
        self::assertSame('Question sur une réservation', $contactMessage->getSubject());
        self::assertSame('Bonjour, est-il possible de modifier les dates de ma réservation ?', $contactMessage->getMessage());
        self::assertNotNull($contactMessage->getSentAt());
    }

    /**
     * @return \Generator<string, array{array{name: string, email: string, subject: string, message: string}}>
     */
    public static function invalidPayloadsProvider(): \Generator
    {
        yield 'empty name' => [[
            'name' => '',
            'email' => 'marie.dupont@example.com',
            'subject' => 'Question sur une réservation',
            'message' => 'Bonjour, pouvez-vous m\'aider ?',
        ]];

        yield 'invalid email' => [[
            'name' => 'Marie Dupont',
            'email' => 'pas-un-email',
            'subject' => 'Question sur une réservation',
            'message' => 'Bonjour, pouvez-vous m\'aider ?',
        ]];

        yield 'empty email' => [[
            'name' => 'Marie Dupont',
            'email' => '',
            'subject' => 'Question sur une réservation',
            'message' => 'Bonjour, pouvez-vous m\'aider ?',
        ]];

        yield 'empty subject' => [[
            'name' => 'Marie Dupont',
            'email' => 'marie.dupont@example.com',
            'subject' => '',
            'message' => 'Bonjour, pouvez-vous m\'aider ?',
        ]];

        yield 'empty message' => [[
            'name' => 'Marie Dupont',
            'email' => 'marie.dupont@example.com',
            'subject' => 'Question sur une réservation',
            'message' => '',
        ]];
    }

    /**
     * @param array{name: string, email: string, subject: string, message: string} $payload
     */
    #[DataProvider('invalidPayloadsProvider')]
    public function test_should_return422_when_payload_is_invalid(array $payload): void
    {
        self::createClient()->request('POST', '/api/contact_messages', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->countContactMessages());
    }
}
