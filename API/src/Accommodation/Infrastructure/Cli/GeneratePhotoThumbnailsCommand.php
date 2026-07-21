<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Cli;

use App\Accommodation\Domain\Entity\Photo;
use App\Accommodation\Domain\Port\ImageTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backfill idempotent des miniatures de photos (convention `<nom>-thumb.webp`).
 * Les nouvelles photos ont leur miniature générée à l'upload ; cette commande
 * couvre le stock existant. Exécutée à chaque déploiement (no-op si tout est
 * déjà généré).
 */
#[AsCommand(
    name: 'app:photos:generate-thumbnails',
    description: 'Génère les miniatures manquantes des photos d\'hébergements',
)]
final class GeneratePhotoThumbnailsCommand extends Command
{
    public function __construct(
        private readonly ImageTransformer $imageTransformer,
        private readonly string $photoUploadDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_dir($this->photoUploadDir)) {
            $output->writeln('No photo upload directory, nothing to do.');

            return Command::SUCCESS;
        }

        $created = 0;
        $present = 0;
        $failed = 0;

        foreach (new \DirectoryIterator($this->photoUploadDir) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();

            if (str_ends_with($filename, '-thumb.webp') || 1 !== preg_match('/\.(webp|jpe?g|png)$/i', $filename)) {
                continue;
            }

            $thumbnailPath = $this->photoUploadDir.'/'.Photo::thumbnailFilename($filename);

            if (is_file($thumbnailPath)) {
                ++$present;
                continue;
            }

            try {
                $thumbnail = $this->imageTransformer->thumbnail(
                    (string) file_get_contents($file->getPathname()),
                    'image/webp',
                );
                file_put_contents($thumbnailPath, $thumbnail->content());
                ++$created;
            } catch (\RuntimeException) {
                ++$failed;
                $output->writeln(\sprintf('<comment>Skipped invalid image: %s</comment>', $filename));
            }
        }

        $output->writeln(\sprintf('Thumbnails: %d created, %d already present, %d failed.', $created, $present, $failed));

        return Command::SUCCESS;
    }
}
