<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Port\ActivityPointRepository;
use Symfony\Component\Uid\Uuid;

/**
 * Returns a single activity point for the admin back-office, used to pre-fill
 * the edit form. Returns null (404) when the point is unknown.
 *
 * @implements ProviderInterface<AdminActivityPointOutput>
 */
final readonly class AdminActivityPointItemProvider implements ProviderInterface
{
    public function __construct(
        private ActivityPointRepository $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AdminActivityPointOutput
    {
        $point = $this->repository->findById(Uuid::fromString((string) $uriVariables['id']));

        if (!$point instanceof ActivityPoint) {
            return null;
        }

        $output = new AdminActivityPointOutput();
        $output->id = $point->getId()->toRfc4122();
        $output->name = $point->getName();
        $output->description = $point->getDescription();
        $output->category = $point->getCategory()->value;
        $output->latitude = $point->getCoordinates()->latitude();
        $output->longitude = $point->getCoordinates()->longitude();
        $output->articleUrl = $point->getArticleUrl();

        return $output;
    }
}
