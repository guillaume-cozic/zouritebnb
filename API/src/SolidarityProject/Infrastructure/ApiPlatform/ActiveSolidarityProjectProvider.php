<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProviderInterface<SolidarityProjectOutput>
 */
final readonly class ActiveSolidarityProjectProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return SolidarityProjectOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $entities = $this->entityManager
            ->getRepository(SolidarityProjectEntity::class)
            ->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (SolidarityProjectEntity $entity): SolidarityProjectOutput => SolidarityProjectOutput::fromEntity($entity),
            $entities,
        );
    }
}
