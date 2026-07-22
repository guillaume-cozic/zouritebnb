<?php

declare(strict_types=1);

namespace App\DevSeed;

use App\ActivityPoint\Infrastructure\Doctrine\ActivityPointEntity;
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
 * Seed les points de la carte des activités de Rodrigues depuis
 * data/activity-points.json (l'inventaire complet des points de la carte,
 * descriptions enrichies par recherche documentaire).
 * UUID fixes : idempotent, relançable sans doublon — mais n'écrase pas les
 * points modifiés/ajoutés depuis l'admin (upsert par id uniquement).
 *
 *     bin/console app:seed:activity-points [--force]
 */
#[AsCommand(name: 'app:seed:activity-points', description: 'Seed les points de la carte des activités depuis le fichier JSON.')]
final class SeedActivityPointsCommand extends Command
{
    private const string SEED_FILE = __DIR__.'/data/activity-points.json';

    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
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

        $points = json_decode((string) file_get_contents(self::SEED_FILE), true, flags: \JSON_THROW_ON_ERROR);
        if (!\is_array($points) || [] === $points) {
            $io->error('Seed file is empty or invalid: '.self::SEED_FILE);

            return Command::FAILURE;
        }

        foreach ($points as $point) {
            $this->upsert($point);
        }

        $this->em->flush();

        $io->success(\sprintf('Seed terminé : %d points sur la carte des activités.', \count($points)));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $point
     */
    private function upsert(array $point): void
    {
        $uuid = Uuid::fromString((string) $point['id']);
        $entity = $this->em->find(ActivityPointEntity::class, $uuid) ?? new ActivityPointEntity();

        $entity
            ->setId($uuid)
            ->setName((string) $point['name'])
            ->setDescription((string) $point['description'])
            ->setCategory((string) $point['category'])
            ->setLatitude((float) $point['latitude'])
            ->setLongitude((float) $point['longitude'])
            ->setArticleUrl(isset($point['articleUrl']) ? (string) $point['articleUrl'] : null);
        $this->em->persist($entity);
    }
}
