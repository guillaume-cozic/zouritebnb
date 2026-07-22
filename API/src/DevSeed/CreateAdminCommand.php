<?php

declare(strict_types=1);

namespace App\DevSeed;

use App\Team\Infrastructure\Doctrine\TeamEntity;
use App\User\Domain\Port\PasswordHasher;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

/**
 * Crée un compte administrateur (ROLE_ADMIN) pour le back-office plateforme.
 * Pensée pour la prod : le mot de passe est demandé en saisie masquée si
 * l'option --password est absente (évite de le laisser dans l'historique
 * shell).
 *
 * Si l'email existe déjà, le compte est promu administrateur sans toucher à
 * son mot de passe. Idempotente : relancer sur un admin existant ne change
 * rien. À la création, une équipe est créée aussi (contrainte NOT NULL :
 * chaque compte appartient à une équipe, comme à l'inscription).
 *
 *     bin/console app:admin:create admin@example.com [--password=...]
 */
#[AsCommand(name: 'app:admin:create', description: 'Crée (ou promeut) un compte administrateur ROLE_ADMIN.')]
final class CreateAdminCommand extends Command
{
    private const int MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordHasher $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email du compte administrateur.')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe (sinon demandé en saisie masquée).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error('Email invalide : '.$email);

            return Command::FAILURE;
        }

        $existing = $this->em->getRepository(UserEntity::class)->findOneBy(['email' => $email]);
        if (null !== $existing) {
            if (\in_array('ROLE_ADMIN', $existing->getRoles(), true)) {
                $io->success(\sprintf('%s est déjà administrateur — rien à faire.', $email));

                return Command::SUCCESS;
            }

            $existing->setRoles([...$existing->getRoles(), 'ROLE_ADMIN']);
            $this->em->flush();
            $io->success(\sprintf('Compte existant %s promu administrateur (mot de passe inchangé).', $email));

            return Command::SUCCESS;
        }

        /** @var string|null $password */
        $password = $input->getOption('password');
        if (null === $password) {
            $password = (string) $io->askHidden('Mot de passe', static function (?string $value): string {
                if (null === $value || '' === $value) {
                    throw new \RuntimeException('Le mot de passe est obligatoire.');
                }

                return $value;
            });
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $io->error(\sprintf('Le mot de passe doit faire au moins %d caractères.', self::MIN_PASSWORD_LENGTH));

            return Command::FAILURE;
        }

        $teamId = Uuid::v7();
        $this->em->persist((new TeamEntity())->setId($teamId));

        $this->em->persist((new UserEntity())
            ->setId(Uuid::v7())
            ->setEmail($email)
            ->setHashedPassword($this->passwordHasher->hash($password))
            ->setTeamId($teamId)
            ->setRoles(['ROLE_ADMIN'])
            ->setVerificationStatus('verified')
            ->setEmailVerified(true)
            ->setEmailVerifiedAt(new \DateTimeImmutable()));
        $this->em->flush();

        $io->success(\sprintf('Administrateur %s créé.', $email));

        return Command::SUCCESS;
    }
}
