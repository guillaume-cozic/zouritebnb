<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\CreateAccommodation;
use App\Accommodation\Domain\Command\CreateAccommodationCommand;
use App\Accommodation\Domain\Exception\InvalidPriceException;
use App\Accommodation\Domain\Port\UuidGenerator;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateAccommodationTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private CreateAccommodation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->useCase = new CreateAccommodation($this->repository);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function testShouldSaveAccommodation(): void
    {
        $expectedId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        UuidGenerator::freeze($expectedId);

        $command = new CreateAccommodationCommand(
            title: 'Chalet montagne',
            description: 'Un chalet au pied des pistes',
            price: 150.0,
        );

        $this->useCase->handle($command);

        $this->assertAccommodationSaved($expectedId, 'Chalet montagne', 'Un chalet au pied des pistes', 150.0);
    }

    #[DataProvider('invalidPriceProvider')]
    public function testShouldNotSaveAccommodationWithInvalidPrice(?float $price, string $expectedMessage): void
    {
        $this->expectException(InvalidPriceException::class);
        $this->expectExceptionMessage($expectedMessage);

        $command = new CreateAccommodationCommand(
            title: 'Chalet montagne',
            description: 'Un chalet au pied des pistes',
            price: $price,
        );

        $this->useCase->handle($command);
    }

    public static function invalidPriceProvider(): \Generator
    {
        yield 'null price' => [null, 'Price is required.'];
        yield 'negative price' => [-50.0, 'Price must be strictly positive, got -50.'];
        yield 'zero price' => [0.0, 'Price must be strictly positive, got 0.'];
    }

    private function assertAccommodationSaved(Uuid $expectedId, string $title, string $description, float $price): void
    {
        $accommodation = $this->repository->findById($expectedId);
        self::assertTrue($expectedId->equals($accommodation->getId()));
        self::assertSame($title, $accommodation->getTitle());
        self::assertSame($description, $accommodation->getDescription());
        self::assertSame($price, $accommodation->getPrice());
    }
}
