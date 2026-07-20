<?php

declare(strict_types=1);

namespace App\DevSeed;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Accommodation\Infrastructure\Doctrine\GalleryEntity;
use App\Accommodation\Infrastructure\Doctrine\PhotoEntity;
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
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Importe les annonces Airbnb de Rodrigues (logements + photos) : scrape la page
 * de recherche, télécharge les images dans var/uploads/photos et upsert
 * logements + photos + galerie via Doctrine. UUID déterministes (dérivés de la
 * réf Airbnb) : idempotent, relançable sans doublon. Refuse l'env prod sauf
 * --force.
 *
 *     bin/console app:import:rodrigues [--force] [--no-photos]
 *
 * NB: scraper Airbnb enfreint leurs CGU — usage perso/ponctuel.
 */
#[AsCommand(name: 'app:import:rodrigues', description: 'Importe les annonces Airbnb de Rodrigues (logements + photos).')]
final class ImportRodriguesCommand extends Command
{
    private const string HOST_TEAM_UUID = '0ade5eed-0002-7000-8000-000000000001';
    private const string HOST_USER_UUID = '0ade5eed-0002-7000-8000-000000000002';
    private const string REGION_RODRIGUES_UUID = '00000000-0000-4000-8000-00000000000a';
    private const string NS_ACCOMMODATION = '0ade5eed-0002-7000-8000-000000000000';
    private const string NS_PHOTO = '0ade5eed-0003-7000-8000-000000000000';

    private const string SEARCH_URL = 'https://www.airbnb.fr/s/Rodrigues/homes?refinement_paths%5B%5D=%2Fhomes&date_picker_type=calendar&place_id=ChIJ-Wk8fjit4yMRdfYL8JMqT74&location_bb=wZ1XbEJ%2BAffBnivOQn1SXQ%3D%3D';
    private const string USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /** @var list<string> */
    private const array VOIES = ['rue', 'allée', 'chemin', 'impasse', 'route'];
    /** @var list<string> */
    private const array NOMS = ['des Cocotiers', 'du Lagon', 'Marivaux', 'de la Plage', 'Mont Lubin', 'des Filaos', 'Gabriel', 'du Récif', "de l'Océan", 'Cabri'];
    /** @var list<string> */
    private const array AMENITIES = ['wifi', 'air_conditioning', 'kitchen', 'parking', 'tv', 'pool', 'sea_view', 'mountain_view', 'terrace', 'balcony', 'bbq', 'garden', 'washing_machine', 'coffee_maker', 'oven', 'microwave', 'iron', 'towels', 'bed_linen', 'blankets', 'extra_pillows', 'walk_in_shower', 'bathtub', 'hot_tub', 'outdoor_furniture', 'books', 'board_games', 'streaming', 'pets_allowed', 'quiet_area'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
        private readonly PasswordHasher $passwordHasher,
        #[Autowire('%kernel.project_dir%/var/uploads/photos')]
        private readonly string $photoUploadDir,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Autorise l\'exécution en environnement prod.')
            ->addOption('no-photos', null, InputOption::VALUE_NONE, 'N\'importe que les logements, sans télécharger les photos.')
            ->addOption('from-file', null, InputOption::VALUE_REQUIRED, 'Importe depuis un page.html déjà scrapé au lieu de requêter Airbnb.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('prod' === $this->environment && !$input->getOption('force')) {
            $io->error('Refusing to import demo data in the prod environment. Use --force to override.');

            return Command::FAILURE;
        }

        $withPhotos = !$input->getOption('no-photos');
        /** @var string|null $fromFile */
        $fromFile = $input->getOption('from-file');

        if (null !== $fromFile) {
            if (!is_file($fromFile)) {
                $io->error('Fichier introuvable : '.$fromFile);

                return Command::FAILURE;
            }
            $io->writeln('→ Import depuis '.$fromFile);
            $html = (string) file_get_contents($fromFile);
        } else {
            $io->writeln('→ Scraping de la page Airbnb...');
            $html = $this->httpClient
                ->request('GET', self::SEARCH_URL, [
                    'headers' => ['User-Agent' => self::USER_AGENT, 'Accept-Language' => 'fr-FR,fr;q=0.9'],
                ])
                ->getContent();
        }

        $listings = $this->parseListings($html);
        if ([] === $listings) {
            $io->error('Aucune annonce trouvée (page anti-bot ou structure Airbnb modifiée ?).');

            return Command::FAILURE;
        }

        $this->ensureHost($io);

        $accommodations = $photos = 0;
        foreach ($listings as $listing) {
            $this->upsertAccommodation($listing);
            ++$accommodations;
            if ($withPhotos) {
                $photos += $this->importPhotos($listing, $io);
            }
        }

        $this->em->flush();

        $io->success(\sprintf('Import terminé : %d logements, %d photos.', $accommodations, $photos));

        return Command::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseListings(string $html): array
    {
        if (!preg_match('/<script id="data-deferred-state-0"[^>]*>(.*?)<\/script>/s', $html, $m)) {
            return [];
        }

        /** @var array<mixed> $data */
        $data = json_decode($m[1], true, 512, \JSON_THROW_ON_ERROR);

        $cards = [];
        $this->collectCards($data, $cards);

        $listings = [];
        $seen = [];
        foreach ($cards as $card) {
            $ref = $this->decodeRef($card['demandStayListing']['id'] ?? '');
            if (null === $ref || isset($seen[$ref])) {
                continue;
            }
            $listing = $this->buildListing($ref, $card);
            if (null === $listing) {
                continue;
            }
            $seen[$ref] = true;
            $listings[] = $listing;
        }

        return $listings;
    }

    /**
     * @param array<mixed>              $node
     * @param list<array<string,mixed>> $cards
     */
    private function collectCards(mixed $node, array &$cards): void
    {
        if (\is_array($node)) {
            if (($node['__typename'] ?? null) === 'StaySearchResult') {
                $cards[] = $node;
            }
            foreach ($node as $value) {
                $this->collectCards($value, $cards);
            }
        }
    }

    private function decodeRef(string $encoded): ?string
    {
        $decoded = base64_decode($encoded, true);
        if (false === $decoded || !str_contains($decoded, ':')) {
            return null;
        }

        return explode(':', $decoded)[1] ?: null;
    }

    /**
     * @param array<string, mixed> $card
     *
     * @return array<string, mixed>|null
     */
    private function buildListing(string $ref, array $card): ?array
    {
        $price = $this->extractPrice($card);
        if (null === $price) {
            return null;
        }

        // Générateur pseudo-aléatoire déterministe par annonce (stable au re-run).
        mt_srand(crc32($ref));

        $title = (string) ($card['title'] ?? '');
        $city = str_contains($title, '⋅') ? trim((string) mb_strrchr($title, '⋅', false)) : 'Rodrigues';
        $city = ltrim($city, '⋅ ');
        $type = $this->mapType(str_contains($title, '⋅') ? explode('⋅', $title)[0] : 'logement');
        $coord = $card['demandStayListing']['location']['coordinate'] ?? [];

        $single = mt_rand(0, 3);
        $double = mt_rand(1, 3);
        $name = $card['nameLocalized']['localizedStringWithTranslationPreference'] ?? ($card['subtitle'] ?? 'Logement Rodrigues');

        $pictures = [];
        foreach ($card['contextualPictures'] ?? [] as $p) {
            if (!empty($p['picture'])) {
                $pictures[] = (string) $p['picture'];
            }
        }

        return [
            'id' => (string) Uuid::v5(Uuid::fromString(self::NS_ACCOMMODATION), $ref),
            'ref' => $ref,
            'title' => mb_substr((string) $name, 0, 255),
            'description' => $this->randomDescription($type, $city),
            'price' => $price,
            'city' => $city,
            'type' => $type,
            'latitude' => isset($coord['latitude']) ? (float) $coord['latitude'] : round($this->randFloat(-19.785, -19.660), 6),
            'longitude' => isset($coord['longitude']) ? (float) $coord['longitude'] : round($this->randFloat(63.360, 63.505), 6),
            'singleBeds' => $single,
            'doubleBeds' => $double,
            'bedrooms' => max(1, mt_rand($single + $double - 1, $single + $double + 1)),
            'bathrooms' => mt_rand(1, 3),
            'maxGuests' => $single + $double * 2 + mt_rand(0, 2),
            'street' => mt_rand(1, 120).', '.self::VOIES[array_rand(self::VOIES)].' '.self::NOMS[array_rand(self::NOMS)],
            'zipCode' => (string) mt_rand(10000, 99999),
            'checkIn' => ['14:00', '15:00', '16:00'][mt_rand(0, 2)],
            'checkOut' => ['10:00', '11:00', '12:00'][mt_rand(0, 2)],
            'amenities' => $this->randomAmenities(),
            'pictures' => $pictures,
        ];
    }

    /**
     * @param array<string, mixed> $card
     */
    private function extractPrice(array $card): ?float
    {
        $sdp = $card['structuredDisplayPrice'] ?? [];
        foreach ($sdp['explanationData']['priceDetails'] ?? [] as $group) {
            foreach ($group['items'] ?? [] as $item) {
                if (preg_match('/x\s*([\d\s ,]+)\s*€/u', (string) ($item['description'] ?? ''), $m)) {
                    return $this->toFloat($m[1]);
                }
            }
        }

        $label = $sdp['primaryLine']['accessibilityLabel'] ?? ($sdp['primaryLine']['price'] ?? '');
        $total = $this->toFloat((string) $label);

        return null !== $total ? round($total / 5, 2) : null;
    }

    private function toFloat(string $text): ?float
    {
        $text = preg_replace('/[^\d,]/u', '', str_replace([' ', "\u{a0}", "\u{202f}"], '', $text)) ?? '';

        return '' !== $text ? (float) str_replace(',', '.', $text) : null;
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
            ->setCountry('Île Rodrigues')
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

    /**
     * @param array<string, mixed> $listing
     */
    private function importPhotos(array $listing, SymfonyStyle $io): int
    {
        /** @var list<string> $pictures */
        $pictures = $listing['pictures'];
        if ([] === $pictures) {
            return 0;
        }

        if (!is_dir($this->photoUploadDir) && !mkdir($this->photoUploadDir, 0o755, true) && !is_dir($this->photoUploadDir)) {
            throw new \RuntimeException('Impossible de créer '.$this->photoUploadDir);
        }

        $accommodationId = Uuid::fromString((string) $listing['id']);
        $photoIds = [];
        foreach ($pictures as $index => $url) {
            $photoUuid = Uuid::v5(Uuid::fromString(self::NS_PHOTO), $listing['ref'].':'.$index);
            $filename = $photoUuid.'.jpg';
            try {
                $content = $this->httpClient
                    ->request('GET', $url, ['headers' => ['User-Agent' => self::USER_AGENT]])
                    ->getContent();
            } catch (\Throwable $e) {
                $io->warning(\sprintf('Image %s:%d ignorée (%s)', $listing['ref'], $index, $e->getMessage()));
                continue;
            }
            file_put_contents($this->photoUploadDir.'/'.$filename, $content);

            $photo = $this->em->find(PhotoEntity::class, $photoUuid) ?? new PhotoEntity();
            $photo
                ->setId($photoUuid)
                ->setAccommodationId($accommodationId)
                ->setFilename($filename)
                ->setOriginalName(mb_substr((string) $listing['title'], 0, 200).'.jpg')
                ->setMimeType('image/jpeg')
                ->setSize(\strlen($content));
            $this->em->persist($photo);
            $photoIds[] = (string) $photoUuid;
        }

        if ([] !== $photoIds) {
            $gallery = $this->em->find(GalleryEntity::class, $accommodationId) ?? new GalleryEntity();
            $gallery->setAccommodationId($accommodationId)->setPhotoIds($photoIds);
            $this->em->persist($gallery);
        }

        return \count($photoIds);
    }

    private function mapType(string $raw): string
    {
        $t = mb_strtolower(trim($raw));

        return match (true) {
            str_contains($t, 'villa') => 'villa',
            str_contains($t, 'bungalow'), str_contains($t, 'tiny') => 'bungalow',
            str_contains($t, 'studio') => 'studio',
            str_contains($t, 'chambre'), str_contains($t, 'room') => 'room',
            str_contains($t, 'appartement'), str_contains($t, 'apart') => 'apartment',
            default => 'house',
        };
    }

    private function randomDescription(string $type, string $city): string
    {
        $intro = [
            "Découvrez ce logement chaleureux situé à {$city}, sur l'île de Rodrigues.",
            "Bienvenue dans ce {$type} niché à {$city}, au cœur de Rodrigues.",
            "Offrez-vous une parenthèse de détente à {$city}, sur la paisible île de Rodrigues.",
            "Ce {$type} vous accueille à {$city} pour un séjour authentique à Rodrigues.",
        ];
        $body = [
            "À quelques minutes des plages de sable blanc et du lagon turquoise, vous profiterez d'un cadre exceptionnel.",
            "Idéal pour explorer les sentiers de randonnée, les îlots et la faune préservée de l'île.",
            'Un havre de paix lumineux et confortable, parfait pour se ressourcer loin de l\'agitation.',
            'Le logement allie confort moderne et charme créole pour un séjour réussi.',
        ];
        $outro = [
            "L'endroit parfait pour des vacances en famille ou entre amis.",
            'Un point de départ idéal pour découvrir Rodrigues à votre rythme.',
            "Réservez dès maintenant pour vivre l'expérience rodriguaise.",
        ];

        return $intro[array_rand($intro)].' '.$body[array_rand($body)].' '.$outro[array_rand($outro)];
    }

    /**
     * @return list<string>
     */
    private function randomAmenities(): array
    {
        $keys = array_rand(self::AMENITIES, mt_rand(6, 12));

        return array_values(array_map(static fn (int $k): string => self::AMENITIES[$k], (array) $keys));
    }

    private function randFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
