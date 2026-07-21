<?php

declare(strict_types=1);

namespace App\DevSeed;

use App\SolidarityProject\Domain\Port\SolidarityProjectImageStorage;
use App\SolidarityProject\Domain\Port\SolidarityProjectImageTransformer;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
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
 * Seed les projets solidaires depuis data/solidarity-projects.json (Réserve de
 * Grande Montagne, pêcheurs, école, récif). UUID fixes : idempotent, relançable
 * sans doublon. Refuse l'env prod sauf --force.
 *
 * Les images vivent dans data/images/ (committées) : elles sont recompressées
 * en WebP taille hero puis stockées localement — aucune dépendance à un CDN
 * externe (l'image du projet mis en avant est le LCP de l'accueil).
 *
 *     bin/console app:seed:solidarity-projects [--force]
 */
#[AsCommand(name: 'app:seed:solidarity-projects', description: 'Seed les projets solidaires depuis le fichier JSON.')]
final class SeedSolidarityProjectsCommand extends Command
{
    private const string SEED_FILE = __DIR__.'/data/solidarity-projects.json';
    private const string IMAGES_DIR = __DIR__.'/data/images';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SolidarityProjectImageStorage $imageStorage,
        private readonly SolidarityProjectImageTransformer $imageTransformer,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        // Base absolue des URLs d'images (l'API sert /uploads/…) : le front
        // consomme imageUrl tel quel, comme pour les uploads via l'admin.
        #[Autowire('%env(DEFAULT_URI)%')]
        private readonly string $baseUri,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Autorise l\'exécution en environnement prod.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('prod' === $this->environment && !$input->getOption('force')) {
            $io->error('Refusing to seed demo data in the prod environment. Use --force to override.');

            return Command::FAILURE;
        }

        $projects = json_decode((string) file_get_contents(self::SEED_FILE), true, flags: \JSON_THROW_ON_ERROR);
        if (!\is_array($projects) || [] === $projects) {
            $io->error('Seed file is empty or invalid: '.self::SEED_FILE);

            return Command::FAILURE;
        }

        foreach ($projects as $project) {
            $this->upsert($project);
        }

        $this->em->flush();

        $io->success(\sprintf('Seed terminé : %d projets solidaires (images locales WebP).', \count($projects)));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $project
     */
    private function upsert(array $project): void
    {
        $uuid = Uuid::fromString((string) $project['id']);
        $entity = $this->em->find(SolidarityProjectEntity::class, $uuid) ?? new SolidarityProjectEntity();

        $entity
            ->setId($uuid)
            ->setImageUrl($this->storeImage($uuid, (string) $project['image']))
            ->setStatus((string) $project['status'])
            ->setIsDefault((bool) ($project['isDefault'] ?? false))
            ->setCreatedAt(new \DateTimeImmutable(\sprintf('-%d days', (int) $project['daysAgo'])))
            ->setTranslations($project['translations']);
        $this->em->persist($entity);
    }

    /**
     * Recompresse l'image committée et la stocke sous un nom stable (UUID du
     * projet) : relancer le seed écrase le même fichier, l'URL ne change pas.
     */
    private function storeImage(Uuid $projectId, string $image): string
    {
        $source = self::IMAGES_DIR.'/'.$image;

        if (!is_file($source)) {
            throw new \RuntimeException(\sprintf('Seed image not found: %s', $source));
        }

        $filename = $projectId->toRfc4122().'.webp';
        $this->imageStorage->store($filename, $this->imageTransformer->toHeroWebp((string) file_get_contents($source)));

        return rtrim($this->baseUri, '/').'/uploads/solidarity-projects/'.$filename;
    }
}
