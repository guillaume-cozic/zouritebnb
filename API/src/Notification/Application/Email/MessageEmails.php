<?php

declare(strict_types=1);

namespace App\Notification\Application\Email;

/**
 * The email notifying a conversation participant that they received a new message. Used in
 * both directions (traveler → host and host → traveler); the wording is neutral.
 */
final readonly class MessageEmails
{
    public function newMessage(
        string $greetingName,
        string $senderName,
        ?string $accommodationTitle,
        string $messageBody,
    ): EmailView {
        return new EmailView(
            template: 'emails/message_posted.html.twig',
            subject: \sprintf('Nouveau message de %s', $senderName),
            variables: [
                'greetingName' => $greetingName,
                'senderName' => $senderName,
                'accommodationTitle' => $accommodationTitle,
                'messageBody' => $messageBody,
            ],
        );
    }
}
