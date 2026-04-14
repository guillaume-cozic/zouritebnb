<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\UseCase\AuthenticateUser;
use App\User\Domain\Command\AuthenticateUserCommand;

/**
 * @implements ProcessorInterface<LoginUserInput, UserOutput>
 */
final readonly class LoginUserProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticateUser $authenticateUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        \assert($data instanceof LoginUserInput);

        $user = $this->authenticateUser->handle(new AuthenticateUserCommand(
            email: $data->email,
            password: $data->password,
        ));

        $output = new UserOutput();
        $output->id = $user->getId()->toRfc4122();
        $output->email = $user->getEmail();
        $output->teamId = $user->getTeamId()->toRfc4122();
        $output->firstName = $user->getFirstName();
        $output->lastName = $user->getLastName();

        return $output;
    }
}
