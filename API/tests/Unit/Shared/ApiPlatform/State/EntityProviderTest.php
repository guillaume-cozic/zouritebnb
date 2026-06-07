<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\ApiPlatform\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use App\Shared\ApiPlatform\State\EntityProvider;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class EntityProviderTest extends TestCase
{
    public function test_should_map_each_entity_of_a_collection_to_a_resource(): void
    {
        $entities = [
            new EntityProviderTestEntity('first'),
            new EntityProviderTestEntity('second'),
        ];

        $collectionProvider = $this->buildCollectionProvider(
            $this->buildResultCollectionExtension(
                new TraversablePaginator(new \ArrayIterator($entities), currentPage: 2.0, itemsPerPage: 10.0, totalItems: 42.0),
            ),
        );
        $itemProvider = $this->buildItemProvider($this->buildResultItemExtension(null));

        $provider = new EntityProvider($collectionProvider, $itemProvider);

        $result = $provider->provide($this->collectionOperation());

        self::assertInstanceOf(TraversablePaginator::class, $result);
        self::assertSame(2.0, $result->getCurrentPage());
        self::assertSame(10.0, $result->getItemsPerPage());
        self::assertSame(42.0, $result->getTotalItems());

        $resources = iterator_to_array($result);
        self::assertCount(2, $resources);
        self::assertContainsOnlyInstancesOf(EntityProviderTestResource::class, $resources);
        self::assertSame('first', $resources[0]->name);
        self::assertSame('second', $resources[1]->name);
    }

    public function test_should_return_an_empty_paginator_when_the_collection_has_no_entity(): void
    {
        $collectionProvider = $this->buildCollectionProvider(
            $this->buildResultCollectionExtension(
                new TraversablePaginator(new \ArrayIterator([]), currentPage: 1.0, itemsPerPage: 10.0, totalItems: 0.0),
            ),
        );
        $itemProvider = $this->buildItemProvider($this->buildResultItemExtension(null));

        $provider = new EntityProvider($collectionProvider, $itemProvider);

        $result = $provider->provide($this->collectionOperation());

        self::assertInstanceOf(TraversablePaginator::class, $result);
        self::assertSame(0.0, $result->getTotalItems());
        self::assertCount(0, iterator_to_array($result));
    }

    public function test_should_map_a_found_item_to_a_resource(): void
    {
        $collectionProvider = $this->buildCollectionProvider($this->buildResultCollectionExtension(
            new TraversablePaginator(new \ArrayIterator([]), 1.0, 10.0, 0.0),
        ));
        $itemProvider = $this->buildItemProvider(
            $this->buildResultItemExtension(new EntityProviderTestEntity('found')),
        );

        $provider = new EntityProvider($collectionProvider, $itemProvider);

        $result = $provider->provide($this->itemOperation());

        self::assertInstanceOf(EntityProviderTestResource::class, $result);
        self::assertSame('found', $result->name);
    }

    public function test_should_return_null_when_the_item_is_not_found(): void
    {
        $collectionProvider = $this->buildCollectionProvider($this->buildResultCollectionExtension(
            new TraversablePaginator(new \ArrayIterator([]), 1.0, 10.0, 0.0),
        ));
        $itemProvider = $this->buildItemProvider($this->buildResultItemExtension(null));

        $provider = new EntityProvider($collectionProvider, $itemProvider);

        $result = $provider->provide($this->itemOperation());

        self::assertNull($result);
    }

    private function collectionOperation(): Operation
    {
        return (new GetCollection())->withClass(EntityProviderTestResource::class);
    }

    private function itemOperation(): Operation
    {
        return (new Get())->withClass(EntityProviderTestResource::class);
    }

    private function buildCollectionProvider(QueryResultCollectionExtensionInterface $extension): CollectionProvider
    {
        return new CollectionProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $this->managerRegistry(),
            [$extension],
        );
    }

    private function buildItemProvider(QueryResultItemExtensionInterface $extension): ItemProvider
    {
        return new ItemProvider(
            $this->createStub(ResourceMetadataCollectionFactoryInterface::class),
            $this->managerRegistry(),
            [$extension],
        );
    }

    /**
     * The registry/manager/repository are mocked: the short-circuiting extension
     * returns the controlled result before any real Doctrine query runs.
     */
    private function managerRegistry(): ManagerRegistry
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('createQueryBuilder')->willReturn($this->createStub(QueryBuilder::class));

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        return $registry;
    }

    private function buildResultCollectionExtension(iterable $result): QueryResultCollectionExtensionInterface
    {
        return new class($result) implements QueryResultCollectionExtensionInterface {
            public function __construct(private readonly iterable $result)
            {
            }

            public function applyToCollection(QueryBuilder $queryBuilder, $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
            {
            }

            public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
            {
                return true;
            }

            public function getResult(QueryBuilder $queryBuilder, ?string $resourceClass = null, ?Operation $operation = null, array $context = []): iterable
            {
                return $this->result;
            }
        };
    }

    private function buildResultItemExtension(?object $result): QueryResultItemExtensionInterface
    {
        return new class($result) implements QueryResultItemExtensionInterface {
            public function __construct(private readonly ?object $result)
            {
            }

            public function applyToItem(QueryBuilder $queryBuilder, $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
            {
            }

            public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
            {
                return true;
            }

            public function getResult(QueryBuilder $queryBuilder, ?string $resourceClass = null, ?Operation $operation = null, array $context = []): ?object
            {
                return $this->result;
            }
        };
    }
}

final class EntityProviderTestEntity
{
    public function __construct(public readonly string $name)
    {
    }
}

final class EntityProviderTestResource implements FromEntityInterface
{
    private function __construct(public readonly string $name)
    {
    }

    public static function fromEntity(object $entity): static
    {
        \assert($entity instanceof EntityProviderTestEntity);

        return new self($entity->name);
    }
}
