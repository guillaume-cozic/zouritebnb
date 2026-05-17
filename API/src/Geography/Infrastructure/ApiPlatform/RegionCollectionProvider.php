<?php

declare(strict_types=1);

namespace App\Geography\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Geography\Application\UseCase\ListRegions;

/**
 * @implements ProviderInterface<RegionOutput>
 */
final readonly class RegionCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListRegions $listRegions,
    ) {
    }

    /**
     * @return RegionOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $regions = $this->listRegions->handle();

        return array_map(static fn ($region) => RegionOutput::fromEntity($region), $regions);
    }
}
