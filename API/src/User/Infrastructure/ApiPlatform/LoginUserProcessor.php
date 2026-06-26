<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\IpRateLimiter;
use App\User\Application\UseCase\AuthenticateUser;
use App\User\Domain\Command\AuthenticateUserCommand;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * @implements ProcessorInterface<LoginUserInput, UserOutput>
 */
final readonly class LoginUserProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticateUser $authenticateUser,
        private JWTTokenManagerInterface $tokenManager,
        private EntityManagerInterface $entityManager,
        private IpRateLimiter $rateLimiter,
        private RateLimiterFactoryInterface $loginLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        if (!$data instanceof LoginUserInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', LoginUserInput::class, get_debug_type($data)));
        }

        // Throttle credential-stuffing / brute-force before touching the DB.
        $this->rateLimiter->enforce($this->loginLimiter);

        $user = $this->authenticateUser->handle(new AuthenticateUserCommand(
            email: $data->email,
            password: $data->password,
        ));

        // Load the Symfony security user (the JWT manager needs a UserInterface).
        $securityUser = $this->entityManager
            ->getRepository(UserEntity::class)
            ->findOneBy(['email' => $user->getEmail()]);
        if (!$securityUser instanceof UserEntity) {
            throw new \RuntimeException('Security user could not be reloaded after authentication.');
        }

        $output = new UserOutput();
        $output->id = $user->getId()->toRfc4122();
        $output->email = $user->getEmail();
        $output->teamId = $user->getTeamId()->toRfc4122();
        $output->firstName = $user->getFirstName();
        $output->lastName = $user->getLastName();
        $output->bio = $user->getBio();
        $output->avatarUrl = null !== $user->getAvatarFilename() ? '/uploads/photos/'.$user->getAvatarFilename() : null;
        $output->verificationStatus = $user->getVerificationStatus()->value;
        $output->emailVerified = $user->isEmailVerified();
        $output->token = $this->tokenManager->create($securityUser);

        return $output;
    }
}
