<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\CreateAccommodationCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Exception\InvalidPriceException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Accommodation\Domain\Port\UuidGenerator;

final readonly class CreateAccommodation
{
    public function __construct(
        private AccommodationRepository $repository,
    ) {
    }

    public function handle(CreateAccommodationCommand $command): string
    {
        if (null === $command->price) {
            throw InvalidPriceException::becauseNull();
        }

        $accommodation = new Accommodation(
            UuidGenerator::generate(),
            $command->title,
            $command->description,
            $command->price,
        );

        $this->repository->save($accommodation);

        return $accommodation->getId()->toRfc4122();
    }
}
