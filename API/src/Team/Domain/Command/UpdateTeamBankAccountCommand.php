<?php

declare(strict_types=1);

namespace App\Team\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateTeamBankAccountCommand
{
    public function __construct(
        public Uuid $teamId,
        public ?string $iban,
        public ?string $bic,
        public ?string $holderName,
    ) {
    }
}
