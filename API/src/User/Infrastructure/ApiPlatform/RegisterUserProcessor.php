<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\IpRateLimiter;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Domain\Entity\Team;
use App\Team\Domain\Port\TeamRepository;
use App\User\Application\UseCase\RegisterUser;
use App\User\Domain\Command\RegisterUserCommand;
use App\User\Infrastructure\Doctrine\UserEntity;
use App\User\Infrastructure\Security\RefreshTokenIssuer;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<RegisterUserInput, UserOutput>
 */
final readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(
        private RegisterUser $registerUser,
        private TeamRepository $teamRepository,
        private TransactionalUseCaseHandler $handler,
        private IpRateLimiter $rateLimiter,
        private RateLimiterFactoryInterface $registerLimiter,
        private JWTTokenManagerInterface $tokenManager,
        private EntityManagerInterface $entityManager,
        private RefreshTokenIssuer $refreshTokenIssuer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        if (!$data instanceof RegisterUserInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', RegisterUserInput::class, get_debug_type($data)));
        }

        // Throttle registration spam before creating anything.
        $this->rateLimiter->enforce($this->registerLimiter);

        $teamId = Uuid::v7();

        /** @var array{userId: string, teamId: string} $ids */
        $ids = $this->handler->execute(function () use ($data, $teamId): array {
            $this->teamRepository->save(Team::create($teamId));
            $userId = $this->registerUser->handle(new RegisterUserCommand(
                email: $data->email,
                password: $data->password,
                teamId: $teamId,
            ));

            return ['userId' => $userId, 'teamId' => $teamId->toRfc4122()];
        });

        // Reload the Symfony security user (the JWT manager needs a UserInterface)
        // so the freshly registered user is logged in straight away.
        $securityUser = $this->entityManager
            ->getRepository(UserEntity::class)
            ->findOneBy(['email' => $data->email]);
        if (!$securityUser instanceof UserEntity) {
            throw new \RuntimeException('Security user could not be reloaded after registration.');
        }

        $output = new UserOutput();
        $output->id = $ids['userId'];
        $output->email = $data->email;
        $output->teamId = $ids['teamId'];
        $output->token = $this->tokenManager->create($securityUser);
        $output->refreshToken = $this->refreshTokenIssuer->issueFor($securityUser);

        return $output;
    }
}
