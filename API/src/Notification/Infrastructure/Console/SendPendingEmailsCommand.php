<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Console;

use App\Notification\Application\UseCase\SendPendingEmails;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Outbox relay. Run on a schedule (cron / Messenger scheduler) to flush pending emails:
 *
 *   bin/console app:emails:send-pending
 */
#[AsCommand(
    name: 'app:emails:send-pending',
    description: 'Send pending emails from the transactional outbox.',
)]
final class SendPendingEmailsCommand extends Command
{
    public function __construct(private readonly SendPendingEmails $sendPendingEmails)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sent = $this->sendPendingEmails->handle();

        $io->success(\sprintf('%d email(s) sent.', $sent));

        return Command::SUCCESS;
    }
}
