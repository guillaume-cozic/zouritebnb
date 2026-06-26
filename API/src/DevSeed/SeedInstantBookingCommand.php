<?php

declare(strict_types=1);

namespace App\DevSeed;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Team\Infrastructure\Doctrine\TeamEntity;
use App\User\Domain\Port\PasswordHasher;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Dev-only seed: a demo host owning two published accommodations in the same city —
 * one with instant booking enabled, one without — so the instant-booking search
 * filter and the auto-confirmed booking flow can be exercised end to end. The host
 * is on its own team, so any other traveler (e.g. guillaume.cozic@gmail.com) can book
 * them. Idempotent: safe to run repeatedly.
 *
 *     bin/console app:seed:instant-booking
 */
#[AsCommand(name: 'app:seed:instant-booking', description: 'Seed published accommodations with and without instant booking.')]
final class SeedInstantBookingCommand extends Command
{
    private const string HOST_TEAM_UUID = '0ade5eed-0001-7000-8000-000000000001';
    private const string HOST_USER_UUID = '0ade5eed-0001-7000-8000-000000000002';
    private const string ACCO_INSTANT_UUID = '0ade5eed-0001-7000-8000-00000000000a';
    private const string ACCO_REQUEST_UUID = '0ade5eed-0001-7000-8000-00000000000b';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordHasher $passwordHasher,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('prod' === $this->environment) {
            $io->error('Refusing to seed demo data in the prod environment.');

            return Command::FAILURE;
        }

        $this->ensureHost($io);

        $this->upsertAccommodation(
            id: self::ACCO_INSTANT_UUID,
            title: 'Studio réservation instantanée',
            instantBooking: true,
            price: 95.0,
            io: $io,
        );
        $this->upsertAccommodation(
            id: self::ACCO_REQUEST_UUID,
            title: 'Studio sur demande',
            instantBooking: false,
            price: 110.0,
            io: $io,
        );

        $this->em->flush();

        $io->success(
            'Seed terminé : 2 logements publiés à Rodrigues (1 en réservation instantanée, 1 sur demande). '
            .'Connectez-vous avec un autre compte et filtrez sur « Réservation instantanée » pour les retrouver.'
        );

        return Command::SUCCESS;
    }

    private function ensureHost(SymfonyStyle $io): void
    {
        $teamId = Uuid::fromString(self::HOST_TEAM_UUID);
        if (null === $this->em->find(TeamEntity::class, $teamId)) {
            $this->em->persist((new TeamEntity())->setId($teamId));
        }

        $hostId = Uuid::fromString(self::HOST_USER_UUID);
        if (null === $this->em->find(UserEntity::class, $hostId)) {
            $this->em->persist((new UserEntity())
                ->setId($hostId)
                ->setEmail('hote.instantanee@example.com')
                ->setHashedPassword($this->passwordHasher->hash('password'))
                ->setTeamId($teamId)
                ->setFirstName('Léa')
                ->setLastName('Hôtesse')
                ->setVerificationStatus('verified')
                ->setRoles([]));
            $io->writeln('Hôte de démo créé : <info>hote.instantanee@example.com</info>');
        }
    }

    private function upsertAccommodation(string $id, string $title, bool $instantBooking, float $price, SymfonyStyle $io): void
    {
        $uuid = Uuid::fromString($id);
        $accommodation = $this->em->find(AccommodationEntity::class, $uuid) ?? new AccommodationEntity();

        $accommodation
            ->setId($uuid)
            ->setTeamId(Uuid::fromString(self::HOST_TEAM_UUID))
            ->setTitle($title)
            ->setDescription('Logement de démonstration pour tester la réservation instantanée.')
            ->setPrice($price)
            ->setStatus('published')
            ->setStreet('2 rue du Lagon')
            ->setCity('Port Mathurin')
            ->setZipCode('00000')
            ->setCountry('Île Rodrigues')
            ->setLatitude(-19.6833)
            ->setLongitude(63.4167)
            ->setBedrooms(1)
            ->setBathrooms(1)
            ->setMaxGuests(2)
            ->setInstantBooking($instantBooking)
            ->setType('studio')
            ->setMinNights(2)
            ->setMaxNights(14);
        $this->em->persist($accommodation);

        $io->writeln(\sprintf(
            'Logement : <info>%s</info> — réservation instantanée : <comment>%s</comment>',
            $title,
            $instantBooking ? 'oui' : 'non',
        ));
    }
}
