<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\Team\Domain\Entity\Team;
use App\Team\Domain\Port\TeamRepository;
use App\User\Application\UseCase\RegisterUser;
use App\User\Domain\Command\RegisterUserCommand;
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
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        if (!$data instanceof RegisterUserInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', RegisterUserInput::class, get_debug_type($data)));
        }

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

        $output = new UserOutput();
        $output->id = $ids['userId'];
        $output->email = $data->email;
        $output->teamId = $ids['teamId'];

        return $output;
    }
}
