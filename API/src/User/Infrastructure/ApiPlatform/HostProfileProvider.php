<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\User\Domain\Port\UserRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<HostProfileOutput>
 */
final readonly class HostProfileProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?HostProfileOutput
    {
        if (!Uuid::isValid((string) $uriVariables['teamId'])) {
            return null;
        }

        $host = $this->repository->findByTeamId(Uuid::fromString((string) $uriVariables['teamId']));

        if (null === $host) {
            return null;
        }

        $output = new HostProfileOutput();
        $output->teamId = $host->getTeamId()->toRfc4122();
        $output->firstName = $host->getFirstName();
        $output->lastName = $host->getLastName();
        $output->bio = $host->getBio();
        $output->avatarUrl = null !== $host->getAvatarFilename() ? '/uploads/photos/'.$host->getAvatarFilename() : null;

        return $output;
    }
}
