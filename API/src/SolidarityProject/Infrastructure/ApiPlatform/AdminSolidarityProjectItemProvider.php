<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;
use Symfony\Component\Uid\Uuid;

/**
 * Returns a single solidarity project for the admin back-office (any status),
 * used to pre-fill the edit page. Returns null (404) when the project is unknown.
 *
 * @implements ProviderInterface<AdminSolidarityProjectOutput>
 */
final readonly class AdminSolidarityProjectItemProvider implements ProviderInterface
{
    public function __construct(
        private SolidarityProjectRepository $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AdminSolidarityProjectOutput
    {
        $project = $this->repository->findById(Uuid::fromString((string) $uriVariables['id']));

        if (!$project instanceof SolidarityProject) {
            return null;
        }

        $output = new AdminSolidarityProjectOutput();
        $output->id = $project->getId()->toRfc4122();
        $output->title = $project->getTitle();
        $output->description = $project->getDescription();
        $output->imageUrl = $project->getImageUrl();
        $output->status = $project->getStatus();
        $output->createdAt = $project->getCreatedAt()->format(\DateTimeInterface::ATOM);
        $output->isDefault = $project->isDefault();
        $output->keyFigures = array_map(
            static fn (KeyFigure $keyFigure): array => ['value' => $keyFigure->value(), 'label' => $keyFigure->label()],
            $project->getKeyFigures(),
        );

        return $output;
    }
}
