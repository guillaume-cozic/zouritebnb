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
use Symfony\Component\Uid\Uuid;

/**
 * Importe l'inventaire complet des points de la carte des activités depuis
 * data/activity-points.json (ou un fichier passé en --file). Contrairement au
 * seed de démo, cette commande est faite pour tourner en prod : les points de
 * la carte sont du contenu réel, maintenu dans le JSON committé.
 *
 * Le fichier est la source de vérité : la table est vidée puis réinsérée à
 * chaque import (le JSON porte toutes les données, coordonnées GPS comprises).
 * Purge + insertions s'exécutent dans une même transaction : un fichier
 * invalide ne laisse jamais une carte vide.
 *
 *     bin/console app:import:activity-points [--file=chemin.json] [--dry-run]
 */
#[AsCommand(name: 'app:import:activity-points', description: 'Importe tous les points de la carte des activités depuis le fichier JSON.')]
final class ImportActivityPointsCommand extends Command
{
    private const string DEFAULT_FILE = __DIR__.'/data/activity-points.json';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin du fichier JSON à importer.', self::DEFAULT_FILE)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche ce qui serait fait sans rien écrire en base.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $file */
        $file = $input->getOption('file');
        if (!is_file($file)) {
            $io->error('Fichier introuvable : '.$file);

            return Command::FAILURE;
        }

        $points = json_decode((string) file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR);
        if (!\is_array($points) || [] === $points) {
            $io->error('Fichier vide ou invalide : '.$file);

            return Command::FAILURE;
        }

        $existing = $this->em->getRepository(ActivityPointEntity::class)->count([]);

        if ($input->getOption('dry-run')) {
            $io->success(\sprintf('[dry-run] %d points supprimés, %d points importés depuis %s.', $existing, \count($points), $file));

            return Command::SUCCESS;
        }

        $this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($points): void {
            $em->createQuery(\sprintf('DELETE FROM %s', ActivityPointEntity::class))->execute();

            foreach ($points as $point) {
                $em->persist((new ActivityPointEntity())
                    ->setId(Uuid::fromString((string) $point['id']))
                    ->setName((string) $point['name'])
                    ->setDescription((string) $point['description'])
                    ->setCategory((string) $point['category'])
                    ->setLatitude((float) $point['latitude'])
                    ->setLongitude((float) $point['longitude'])
                    ->setArticleUrl(isset($point['articleUrl']) ? (string) $point['articleUrl'] : null));
            }
        });

        $io->success(\sprintf('Import terminé : %d points supprimés, %d points importés.', $existing, \count($points)));

        return Command::SUCCESS;
    }
}
