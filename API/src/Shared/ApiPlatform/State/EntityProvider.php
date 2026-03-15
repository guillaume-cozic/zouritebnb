<?php

declare(strict_types=1);

namespace App\Shared\ApiPlatform\State;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;

/**
 * @implements ProviderInterface<FromEntityInterface>
 */
final readonly class EntityProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): FromEntityInterface|TraversablePaginator|null
    {
        /** @var class-string<FromEntityInterface> $resourceClass */
        $resourceClass = $operation->getClass();

        if ($operation instanceof CollectionOperationInterface) {
            /** @var Paginator $paginator */
            $paginator = $this->collectionProvider->provide($operation, $uriVariables, $context);

            $outputs = [];
            foreach ($paginator as $entity) {
                $outputs[] = $resourceClass::fromEntity($entity);
            }

            return new TraversablePaginator(
                new \ArrayIterator($outputs),
                $paginator->getCurrentPage(),
                $paginator->getItemsPerPage(),
                $paginator->getTotalItems(),
            );
        }

        $entity = $this->itemProvider->provide($operation, $uriVariables, $context);

        return $entity ? $resourceClass::fromEntity($entity) : null;
    }
}
