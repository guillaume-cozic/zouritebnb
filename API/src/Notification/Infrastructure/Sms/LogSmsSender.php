<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Sms;

use App\Notification\Domain\Entity\SmsMessage;
use App\Notification\Domain\Port\SmsSender;
use Psr\Log\LoggerInterface;

/**
 * Placeholder SMS sender: logs the message instead of calling a real SMS gateway. Swap this
 * adapter for a provider-backed one (Twilio, Vonage, OVH…) when integrating a provider.
 */
final readonly class LogSmsSender implements SmsSender
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function send(SmsMessage $sms): void
    {
        $this->logger->info('[SMS] to {recipient}: {text}', [
            'recipient' => $sms->getRecipient()->toString(),
            'text' => $sms->getText(),
        ]);
    }
}
