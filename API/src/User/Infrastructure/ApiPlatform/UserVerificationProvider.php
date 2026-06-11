<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Port\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<UserVerificationOutput>
 */
final readonly class UserVerificationProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $repository,
        private CurrentUser $currentUser,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserVerificationOutput
    {
        $id = Uuid::fromString($uriVariables['id']);

        // Object-level authorization: a user may only read their own KYC status.
        if (!$this->currentUser->id()->equals($id)) {
            throw new AccessDeniedHttpException('You can only read your own identity verification.');
        }

        $user = $this->repository->findById($id);

        if (null === $user) {
            throw UserNotFoundException::becauseNotFound($id->toRfc4122());
        }

        $output = new UserVerificationOutput();
        $output->userId = $user->getId()->toRfc4122();
        $output->status = $user->getVerificationStatus()->value;
        $output->documentType = $user->getDocumentType()?->value;
        $output->verifiedAt = $user->getVerifiedAt()?->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
