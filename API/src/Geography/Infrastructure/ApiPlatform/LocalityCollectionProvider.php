<?php

declare(strict_types=1);

namespace App\Geography\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Geography\Application\UseCase\ListLocalities;
use App\Geography\Domain\Command\ListLocalitiesCommand;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<LocalityOutput>
 */
final readonly class LocalityCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListLocalities $listLocalities,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return LocalityOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $regionCodeParam = $request?->query->get('regionCode');
        $regionCode = (\is_string($regionCodeParam) && '' !== $regionCodeParam) ? $regionCodeParam : null;

        $localities = $this->listLocalities->handle(new ListLocalitiesCommand(regionCode: $regionCode));

        return array_map(static fn ($locality) => LocalityOutput::fromEntity($locality), $localities);
    }
}
