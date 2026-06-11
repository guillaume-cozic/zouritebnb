<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\UseCase\AuthenticateUser;
use App\User\Domain\Command\AuthenticateUserCommand;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * @implements ProcessorInterface<LoginUserInput, UserOutput>
 */
final readonly class LoginUserProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticateUser $authenticateUser,
        private JWTTokenManagerInterface $tokenManager,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        \assert($data instanceof LoginUserInput);

        $user = $this->authenticateUser->handle(new AuthenticateUserCommand(
            email: $data->email,
            password: $data->password,
        ));

        // Load the Symfony security user (the JWT manager needs a UserInterface).
        $securityUser = $this->entityManager
            ->getRepository(UserEntity::class)
            ->findOneBy(['email' => $user->getEmail()]);
        \assert($securityUser instanceof UserEntity);

        $output = new UserOutput();
        $output->id = $user->getId()->toRfc4122();
        $output->email = $user->getEmail();
        $output->teamId = $user->getTeamId()->toRfc4122();
        $output->firstName = $user->getFirstName();
        $output->lastName = $user->getLastName();
        $output->verificationStatus = $user->getVerificationStatus()->value;
        $output->token = $this->tokenManager->create($securityUser);

        return $output;
    }
}
