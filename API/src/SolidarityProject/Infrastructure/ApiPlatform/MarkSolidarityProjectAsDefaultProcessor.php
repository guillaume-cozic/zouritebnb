<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use App\SolidarityProject\Application\UseCase\MarkSolidarityProjectAsDefault;
use App\SolidarityProject\Domain\Command\MarkSolidarityProjectAsDefaultCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class MarkSolidarityProjectAsDefaultProcessor implements ProcessorInterface
{
    public function __construct(
        private MarkSolidarityProjectAsDefault $useCase,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->useCase->handle(new MarkSolidarityProjectAsDefaultCommand(
            projectId: Uuid::fromString($uriVariables['id']),
        )));
    }
}
