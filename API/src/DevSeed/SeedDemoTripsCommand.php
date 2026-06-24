<?php

declare(strict_types=1);

namespace App\DevSeed;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Conversation\Infrastructure\Doctrine\ConversationEntity;
use App\Conversation\Infrastructure\Doctrine\MessageEntity;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
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
 * Dev-only seed: ensures the traveler guillaume.cozic@gmail.com exists and owns two
 * confirmed (accepted) reservations at a demo host — one upcoming trip and one past,
 * completed stay — each with a conversation. Idempotent: safe to run repeatedly.
 *
 *     bin/console app:seed:demo-trips
 */
#[AsCommand(name: 'app:seed:demo-trips', description: 'Seed guillaume.cozic@gmail.com with an accepted reservation and a past trip.')]
final class SeedDemoTripsCommand extends Command
{
    private const string GUEST_EMAIL = 'guillaume.cozic@gmail.com';
    private const string GUEST_PASSWORD = 'password';

    // Deterministic ids so re-running the seed updates rather than duplicates.
    private const string HOST_TEAM_UUID = '0ade5eed-0000-7000-8000-000000000001';
    private const string HOST_USER_UUID = '0ade5eed-0000-7000-8000-000000000002';
    private const string ACCO_PAST_UUID = '0ade5eed-0000-7000-8000-00000000000a';
    private const string ACCO_UPCOMING_UUID = '0ade5eed-0000-7000-8000-00000000000b';
    private const string RES_PAST_UUID = '0ade5eed-0000-7000-8000-000000000010';
    private const string RES_UPCOMING_UUID = '0ade5eed-0000-7000-8000-000000000011';
    private const string CONV_PAST_UUID = '0ade5eed-0000-7000-8000-000000000020';
    private const string CONV_UPCOMING_UUID = '0ade5eed-0000-7000-8000-000000000021';

    private bool $guestCreated = false;

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

        $guest = $this->ensureGuest($io);
        $host = $this->ensureHost($io);
        $accoPast = $this->ensureAccommodation(self::ACCO_PAST_UUID, 'Cabane sur pilotis', 'Le Morne', 'Île Maurice', 140.0, $io);
        $accoUpcoming = $this->ensureAccommodation(self::ACCO_UPCOMING_UUID, 'Villa vue lagon', 'Trou d\'Eau Douce', 'Île Maurice', 220.0, $io);

        $today = new \DateTimeImmutable('today');

        // Past, completed stay (7 nights, ~2 months ago).
        $this->upsertReservation(
            id: self::RES_PAST_UUID,
            accommodation: $accoPast,
            guest: $guest,
            checkIn: $today->modify('-60 days'),
            nights: 7,
            io: $io,
        );
        $this->ensureConversation(
            id: self::CONV_PAST_UUID,
            reservationId: self::RES_PAST_UUID,
            accommodation: $accoPast,
            guest: $guest,
            host: $host,
            createdAt: $today->modify('-70 days'),
            past: true,
            io: $io,
        );

        // Upcoming, accepted reservation (5 nights, in ~3 weeks).
        $this->upsertReservation(
            id: self::RES_UPCOMING_UUID,
            accommodation: $accoUpcoming,
            guest: $guest,
            checkIn: $today->modify('+20 days'),
            nights: 5,
            io: $io,
        );
        $this->ensureConversation(
            id: self::CONV_UPCOMING_UUID,
            reservationId: self::RES_UPCOMING_UUID,
            accommodation: $accoUpcoming,
            guest: $guest,
            host: $host,
            createdAt: $today->modify('-2 days'),
            past: false,
            io: $io,
        );

        $this->em->flush();

        $credentials = $this->guestCreated
            ? \sprintf(' Mot de passe : "%s".', self::GUEST_PASSWORD)
            : ' (compte existant : mot de passe inchangé).';
        $io->success(\sprintf(
            'Seed terminé pour %s : 1 réservation à venir (acceptée) + 1 voyage passé.%s',
            self::GUEST_EMAIL,
            $credentials,
        ));

        return Command::SUCCESS;
    }

    private function ensureGuest(SymfonyStyle $io): UserEntity
    {
        $existing = $this->em->getRepository(UserEntity::class)->findOneBy(['email' => self::GUEST_EMAIL]);
        if (null !== $existing) {
            $io->writeln(\sprintf('Voyageur existant réutilisé : <info>%s</info>', self::GUEST_EMAIL));

            return $existing;
        }

        $teamId = Uuid::v7();
        $this->em->persist((new TeamEntity())->setId($teamId));

        $guest = (new UserEntity())
            ->setId(Uuid::v7())
            ->setEmail(self::GUEST_EMAIL)
            ->setHashedPassword($this->passwordHasher->hash(self::GUEST_PASSWORD))
            ->setTeamId($teamId)
            ->setFirstName('Guillaume')
            ->setLastName('Cozic')
            ->setVerificationStatus('verified')
            ->setRoles([]);
        $this->em->persist($guest);
        $this->guestCreated = true;
        $io->writeln(\sprintf('Voyageur créé : <info>%s</info>', self::GUEST_EMAIL));

        return $guest;
    }

    private function ensureHost(SymfonyStyle $io): UserEntity
    {
        $teamId = Uuid::fromString(self::HOST_TEAM_UUID);
        if (null === $this->em->find(TeamEntity::class, $teamId)) {
            $this->em->persist((new TeamEntity())->setId($teamId));
        }

        $hostId = Uuid::fromString(self::HOST_USER_UUID);
        $host = $this->em->find(UserEntity::class, $hostId);
        if (null === $host) {
            $host = (new UserEntity())
                ->setId($hostId)
                ->setEmail('marie.hote@example.com')
                ->setHashedPassword($this->passwordHasher->hash(self::GUEST_PASSWORD))
                ->setTeamId($teamId)
                ->setFirstName('Marie')
                ->setLastName('Hôte')
                ->setVerificationStatus('verified')
                ->setRoles([]);
            $this->em->persist($host);
            $io->writeln('Hôte de démo créé : <info>marie.hote@example.com</info>');
        }

        return $host;
    }

    private function ensureAccommodation(string $id, string $title, string $city, string $country, float $price, SymfonyStyle $io): AccommodationEntity
    {
        $uuid = Uuid::fromString($id);
        $existing = $this->em->find(AccommodationEntity::class, $uuid);
        if (null !== $existing) {
            return $existing;
        }

        $accommodation = (new AccommodationEntity())
            ->setId($uuid)
            ->setTeamId(Uuid::fromString(self::HOST_TEAM_UUID))
            ->setTitle($title)
            ->setDescription('Logement de démonstration pour les jeux de données locaux.')
            ->setPrice($price)
            ->setStatus('published')
            ->setStreet('1 chemin du Lagon')
            ->setCity($city)
            ->setZipCode('00000')
            ->setCountry($country)
            ->setBedrooms(2)
            ->setBathrooms(1)
            ->setMaxGuests(4);
        $this->em->persist($accommodation);
        $io->writeln(\sprintf('Logement créé : <info>%s</info> (%s)', $title, $city));

        return $accommodation;
    }

    private function upsertReservation(
        string $id,
        AccommodationEntity $accommodation,
        UserEntity $guest,
        \DateTimeImmutable $checkIn,
        int $nights,
        SymfonyStyle $io,
    ): void {
        $uuid = Uuid::fromString($id);
        $checkOut = $checkIn->modify(\sprintf('+%d days', $nights));
        $pricePerNight = (float) $accommodation->getPrice();
        $totalPrice = round($pricePerNight * $nights, 2);

        $entity = $this->em->find(ReservationEntity::class, $uuid) ?? new ReservationEntity();
        $entity
            ->setId($uuid)
            ->setAccommodationId($accommodation->getId())
            ->setTeamId(Uuid::fromString(self::HOST_TEAM_UUID))
            ->setGuestUserId($guest->getId())
            ->setCheckIn($checkIn)
            ->setCheckOut($checkOut)
            ->setGuestName(trim($guest->getFirstName().' '.$guest->getLastName()))
            ->setStatus('confirmed')
            ->setTotalPrice($totalPrice)
            ->setPricePerNight($pricePerNight)
            ->setAppliedDiscountPercentage(null)
            ->setCommissionAmount(round($totalPrice * ReservationPrice::COMMISSION_RATE, 2))
            ->setDonationAmount(round($totalPrice * ReservationPrice::DONATION_RATE, 2));
        $this->em->persist($entity);

        $io->writeln(\sprintf(
            'Réservation confirmée : <info>%s → %s</info> (%s)',
            $checkIn->format('d/m/Y'),
            $checkOut->format('d/m/Y'),
            $accommodation->getTitle(),
        ));
    }

    private function ensureConversation(
        string $id,
        string $reservationId,
        AccommodationEntity $accommodation,
        UserEntity $guest,
        UserEntity $host,
        \DateTimeImmutable $createdAt,
        bool $past,
        SymfonyStyle $io,
    ): void {
        $uuid = Uuid::fromString($id);
        if (null !== $this->em->find(ConversationEntity::class, $uuid)) {
            return;
        }

        $conversation = (new ConversationEntity())
            ->setId($uuid)
            ->setReservationId(Uuid::fromString($reservationId))
            ->setAccommodationId($accommodation->getId())
            ->setTeamId(Uuid::fromString(self::HOST_TEAM_UUID))
            ->setGuestUserId($guest->getId())
            ->setCreatedAt($createdAt);

        $conversation->addMessage((new MessageEntity())
            ->setId(Uuid::v7())
            ->setAuthorUserId(null)
            ->setBody(\sprintf('Demande de réservation pour « %s ».', $accommodation->getTitle()))
            ->setSentAt($createdAt)
            ->setIsSystem(true));

        $conversation->addMessage((new MessageEntity())
            ->setId(Uuid::v7())
            ->setAuthorUserId($host->getId())
            ->setBody('Bonjour Guillaume, votre réservation est confirmée. Au plaisir de vous accueillir !')
            ->setSentAt($createdAt->modify('+30 minutes'))
            ->setIsSystem(false));

        $conversation->addMessage((new MessageEntity())
            ->setId(Uuid::v7())
            ->setAuthorUserId($guest->getId())
            ->setBody($past ? 'Merci pour ce superbe séjour !' : 'Super, merci ! On a hâte.')
            ->setSentAt($createdAt->modify('+1 hour'))
            ->setIsSystem(false));

        $this->em->persist($conversation);
        $io->writeln('Conversation créée pour la réservation.');
    }
}
