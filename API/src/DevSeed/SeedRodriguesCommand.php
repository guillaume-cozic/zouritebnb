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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Dev-only seed: imports the Rodrigues listings frozen in
 * data/rodrigues-accommodations.json (scraped from Airbnb then generated once,
 * see scrape-rodrigues.sh) as published accommodations owned by a demo host.
 * UUIDs are deterministic (derived from the Airbnb ref), so the command is
 * idempotent: re-running updates existing rows instead of duplicating them.
 *
 *     bin/console app:seed:rodrigues
 */
#[AsCommand(name: 'app:seed:rodrigues', description: 'Seed the scraped Rodrigues accommodations from the JSON seed file.')]
final class SeedRodriguesCommand extends Command
{
    private const string HOST_TEAM_UUID = '0ade5eed-0002-7000-8000-000000000001';
    private const string HOST_USER_UUID = '0ade5eed-0002-7000-8000-000000000002';
    private const string REGION_RODRIGUES_UUID = '00000000-0000-4000-8000-00000000000a';
    private const string SEED_FILE = __DIR__.'/data/rodrigues-accommodations.json';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordHasher $passwordHasher,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Autorise l\'exécution en environnement prod (données de démo générées).',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('prod' === $this->environment && !$input->getOption('force')) {
            $io->error('Refusing to seed demo data in the prod environment. Use --force to override.');

            return Command::FAILURE;
        }

        $listings = json_decode((string) file_get_contents(self::SEED_FILE), true, flags: \JSON_THROW_ON_ERROR);
        if (!\is_array($listings) || [] === $listings) {
            $io->error('Seed file is empty or invalid: '.self::SEED_FILE);

            return Command::FAILURE;
        }

        $this->ensureHost($io);

        foreach ($listings as $listing) {
            $this->upsertAccommodation($listing);
        }

        $this->em->flush();

        $io->success(\sprintf('Seed terminé : %d logements Rodrigues publiés.', \count($listings)));

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
                ->setEmail('hote.rodrigues@example.com')
                ->setHashedPassword($this->passwordHasher->hash('password'))
                ->setTeamId($teamId)
                ->setFirstName('Rodrigues')
                ->setLastName('Séjours')
                ->setVerificationStatus('verified')
                ->setRoles([]));
            $io->writeln('Hôte de démo créé : <info>hote.rodrigues@example.com</info>');
        }
    }

    /**
     * @param array<string, mixed> $listing
     */
    private function upsertAccommodation(array $listing): void
    {
        $uuid = Uuid::fromString((string) $listing['id']);
        $accommodation = $this->em->find(AccommodationEntity::class, $uuid) ?? new AccommodationEntity();

        $accommodation
            ->setId($uuid)
            ->setTeamId(Uuid::fromString(self::HOST_TEAM_UUID))
            ->setRegionId(Uuid::fromString(self::REGION_RODRIGUES_UUID))
            ->setTitle((string) $listing['title'])
            ->setDescription((string) $listing['description'])
            ->setPrice((float) $listing['price'])
            ->setStatus('published')
            ->setType((string) $listing['type'])
            ->setStreet((string) $listing['street'])
            ->setCity((string) $listing['city'])
            ->setZipCode((string) $listing['zipCode'])
            ->setCountry((string) $listing['country'])
            ->setLatitude((float) $listing['latitude'])
            ->setLongitude((float) $listing['longitude'])
            ->setBedrooms((int) $listing['bedrooms'])
            ->setBathrooms((int) $listing['bathrooms'])
            ->setMaxGuests((int) $listing['maxGuests'])
            ->setSingleBeds((int) $listing['singleBeds'])
            ->setDoubleBeds((int) $listing['doubleBeds'])
            ->setAmenities($listing['amenities'])
            ->setCheckIn((string) $listing['checkIn'])
            ->setCheckOut((string) $listing['checkOut']);
        $this->em->persist($accommodation);
    }
}
