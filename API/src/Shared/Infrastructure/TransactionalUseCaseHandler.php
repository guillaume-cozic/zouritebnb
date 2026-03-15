<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Domain\Port\TransactionManager;

final readonly class TransactionalUseCaseHandler
{
    public function __construct(private TransactionManager $transactionManager)
    {
    }

    public function execute(callable $operation): mixed
    {
        return $this->transactionManager->transactional($operation);
    }
}
