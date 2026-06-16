<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Console;

use App\Notification\Application\UseCase\ResendEmail;
use App\Notification\Domain\Command\ResendEmailCommand as ResendEmailUseCaseCommand;
use App\Notification\Domain\Exception\EmailDeliveryException;
use App\Notification\Domain\Exception\OutboxEmailNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

/**
 * Re-sends a single email from the outbox by its id (e.g. a dead-lettered email that
 * failed after a network outage):
 *
 *   bin/console app:emails:resend <id>
 */
#[AsCommand(
    name: 'app:emails:resend',
    description: 'Re-send a single email from the outbox by its id.',
)]
final class ResendEmailCommand extends Command
{
    public function __construct(private readonly ResendEmail $resendEmail)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'The outbox email id (UUID).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rawId = (string) $input->getArgument('id');

        try {
            $id = Uuid::fromString($rawId);
        } catch (\InvalidArgumentException) {
            $io->error(\sprintf('"%s" is not a valid UUID.', $rawId));

            return Command::INVALID;
        }

        try {
            $this->resendEmail->handle(new ResendEmailUseCaseCommand($id));
        } catch (OutboxEmailNotFoundException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        } catch (EmailDeliveryException $exception) {
            $io->error(\sprintf('Delivery failed, the attempt was recorded: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        $io->success(\sprintf('Email %s re-sent.', $id->toRfc4122()));

        return Command::SUCCESS;
    }
}
