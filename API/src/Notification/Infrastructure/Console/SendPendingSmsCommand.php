<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Console;

use App\Notification\Application\UseCase\SendPendingSms;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * SMS outbox relay. Run on a schedule (cron / Messenger scheduler) to flush pending SMS:
 *
 *   bin/console app:sms:send-pending
 */
#[AsCommand(
    name: 'app:sms:send-pending',
    description: 'Send pending SMS from the transactional outbox.',
)]
final class SendPendingSmsCommand extends Command
{
    public function __construct(private readonly SendPendingSms $sendPendingSms)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sent = $this->sendPendingSms->handle();

        $io->success(\sprintf('%d SMS sent.', $sent));

        return Command::SUCCESS;
    }
}
