<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\IpRateLimiter;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Domain\Entity\Team;
use App\Team\Domain\Port\TeamRepository;
use App\User\Application\UseCase\AuthenticateSocialUser;
use App\User\Domain\Command\AuthenticateSocialUserCommand;
use App\User\Domain\Entity\SocialAuthenticationResult;
use App\User\Domain\Entity\SocialProvider;
use App\User\Infrastructure\Doctrine\UserEntity;
use App\User\Infrastructure\Security\RefreshTokenIssuer;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<SocialLoginInput, UserOutput>
 */
final readonly class SocialLoginProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticateSocialUser $authenticateSocialUser,
        private TeamRepository $teamRepository,
        private TransactionalUseCaseHandler $handler,
        private IpRateLimiter $rateLimiter,
        private RateLimiterFactoryInterface $socialLoginLimiter,
        private JWTTokenManagerInterface $tokenManager,
        private EntityManagerInterface $entityManager,
        private RefreshTokenIssuer $refreshTokenIssuer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        if (!$data instanceof SocialLoginInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', SocialLoginInput::class, get_debug_type($data)));
        }

        // Throttle token brute-forcing / provider hammering before any network call.
        $this->rateLimiter->enforce($this->socialLoginLimiter);

        $teamId = Uuid::v7();

        /** @var SocialAuthenticationResult $result */
        $result = $this->handler->execute(function () use ($data, $teamId): SocialAuthenticationResult {
            $result = $this->authenticateSocialUser->handle(new AuthenticateSocialUserCommand(
                provider: SocialProvider::fromString($data->provider),
                token: $data->token,
                teamId: $teamId,
            ));

            // A first-time social sign-in registers a user: give it its own team,
            // exactly like the classic registration flow does.
            if ($result->registered) {
                $this->teamRepository->save(Team::create($teamId));
            }

            return $result;
        });

        $user = $result->user;

        // Load the Symfony security user (the JWT manager needs a UserInterface).
        $securityUser = $this->entityManager
            ->getRepository(UserEntity::class)
            ->findOneBy(['email' => $user->getEmail()]);
        if (!$securityUser instanceof UserEntity) {
            throw new \RuntimeException('Security user could not be reloaded after social authentication.');
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
        $output->refreshToken = $this->refreshTokenIssuer->issueFor($securityUser);

        return $output;
    }
}
