<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Returns a single solidarity project for the public site, with its content served
 * in the locale negotiated from the Accept-Language header. Returns null (404) when
 * the project is unknown.
 *
 * @implements ProviderInterface<SolidarityProjectOutput>
 */
final readonly class SolidarityProjectItemProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LocaleResolver $localeResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?SolidarityProjectOutput
    {
        if (!Uuid::isValid((string) ($uriVariables['id'] ?? ''))) {
            return null;
        }

        $entity = $this->entityManager
            ->getRepository(SolidarityProjectEntity::class)
            ->find(Uuid::fromString((string) $uriVariables['id']));

        if (!$entity instanceof SolidarityProjectEntity) {
            return null;
        }

        return SolidarityProjectOutput::fromEntity($entity, $this->localeResolver->current());
    }
}
