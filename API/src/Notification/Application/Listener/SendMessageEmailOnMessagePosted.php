<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Notification\Application\Email\MessageEmails;
use App\Notification\Application\UseCase\QueueEmail;
use App\Notification\Domain\Command\QueueEmailCommand;
use App\Shared\Domain\Event\MessagePosted;
use App\Shared\Domain\Port\AccommodationSummaryProvider;
use App\Shared\Domain\Port\ConversationMessageProvider;
use App\Shared\Domain\Port\TeamContactProvider;
use App\Shared\Domain\Port\UserContact;
use App\Shared\Domain\Port\UserContactProvider;
use Symfony\Component\Uid\Uuid;

/**
 * Emails the recipient of a conversation message: when the guest writes, the host(s) are
 * notified; when a host writes, the guest is notified. System messages are skipped.
 */
final readonly class SendMessageEmailOnMessagePosted
{
    public function __construct(
        private ConversationMessageProvider $messages,
        private TeamContactProvider $teamContacts,
        private UserContactProvider $contacts,
        private AccommodationSummaryProvider $accommodations,
        private MessageEmails $emails,
        private QueueEmail $queueEmail,
    ) {
    }

    public function __invoke(MessagePosted $event): void
    {
        if ($event->isSystem) {
            return;
        }

        $message = $this->messages->findMessage($event->conversationId, $event->messageId);

        if (null === $message || $message->isSystem || null === $message->authorUserId) {
            return;
        }

        $authorIsGuest = $message->authorUserId->equals($message->guestUserId);

        if ($authorIsGuest) {
            $recipients = $this->teamContacts->contactsOf($message->teamId);
            $senderName = $this->displayName($message->guestUserId, 'Le voyageur');
        } else {
            $guest = $this->contacts->contactOf($message->guestUserId);
            $recipients = null !== $guest ? [$guest] : [];
            $senderName = $this->displayName($message->authorUserId, 'L\'hôte');
        }

        $accommodation = $this->accommodations->summaryOf($message->accommodationId);
        $accommodationTitle = $accommodation?->title;

        foreach ($recipients as $recipient) {
            // Don't email authors a copy of their own message (e.g. a co-host who wrote it).
            if ($recipient->userId->equals($message->authorUserId)) {
                continue;
            }

            $view = $this->emails->newMessage($recipient->greetingName(), $senderName, $accommodationTitle, $message->body);

            $this->queueEmail->handle(new QueueEmailCommand(
                recipientEmail: $recipient->email,
                recipientName: $recipient->firstName,
                subject: $view->subject,
                template: $view->template,
                variables: $view->variables,
            ));
        }
    }

    private function displayName(Uuid $userId, string $fallback): string
    {
        $contact = $this->contacts->contactOf($userId);

        return $contact instanceof UserContact ? $contact->greetingName() : $fallback;
    }
}
