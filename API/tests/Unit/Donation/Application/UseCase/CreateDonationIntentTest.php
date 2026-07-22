<?php

declare(strict_types=1);

namespace App\Tests\Unit\Donation\Application\UseCase;

use App\Donation\Application\UseCase\CreateDonationIntent;
use App\Donation\Domain\Command\CreateDonationIntentCommand;
use App\Donation\Domain\Entity\DonationStatus;
use App\Donation\Domain\Exception\InvalidDonationException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Donation\Infrastructure\InMemoryDonationGateway;
use App\Tests\Unit\Donation\Infrastructure\InMemoryDonationRepository;
use App\Tests\Unit\Donation\Infrastructure\InMemorySolidarityProjectDonationChecker;
use App\Tests\Unit\Payment\Infrastructure\FixedClock;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateDonationIntentTest extends TestCase
{
    private InMemoryDonationRepository $repository;
    private InMemoryDonationGateway $gateway;
    private InMemoryEventBus $eventBus;
    private FixedClock $clock;
    private InMemorySolidarityProjectDonationChecker $projectChecker;
    private CreateDonationIntent $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryDonationRepository();
        $this->gateway = new InMemoryDonationGateway();
        $this->eventBus = new InMemoryEventBus();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-07-22T10:00:00+00:00'));
        $this->projectChecker = new InMemorySolidarityProjectDonationChecker();
        $this->useCase = new CreateDonationIntent(
            $this->repository,
            $this->gateway,
            $this->eventBus,
            $this->clock,
            $this->projectChecker,
        );
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_persist_pending_donation_and_return_created_intent(): void
    {
        $projectId = Uuid::fromString('01981e2f-dead-7000-beef-0000000000a1');
        $donationId = Uuid::fromString('01981e2f-dead-7000-beef-000000000001');
        UuidGenerator::queue([$donationId]);
        $this->projectChecker->activate($projectId);

        $result = $this->useCase->handle(new CreateDonationIntentCommand(
            solidarityProjectId: $projectId,
            amountCents: 2500,
        ));

        self::assertSame('pi_donation_1', $result->paymentIntentId);
        self::assertSame('pi_donation_1_secret_1', $result->clientSecret);

        self::assertCount(1, $this->gateway->calls);
        self::assertSame(2500, $this->gateway->calls[0]['amountCents']);
        self::assertSame('eur', $this->gateway->calls[0]['currency']);
        self::assertSame(\sprintf('Don projet solidaire %s', $projectId->toRfc4122()), $this->gateway->calls[0]['description']);
        self::assertSame([
            'solidarityProjectId' => $projectId->toRfc4122(),
            'type' => 'donation',
        ], $this->gateway->calls[0]['metadata']);

        $stored = $this->repository->findById($donationId);
        self::assertNotNull($stored);
        self::assertSame(DonationStatus::Pending, $stored->getStatus());
        self::assertSame(2500, $stored->getAmountCents());
        self::assertSame('eur', $stored->getCurrency());
        self::assertSame('pi_donation_1', $stored->getStripePaymentIntentId());
        self::assertTrue($stored->getSolidarityProjectId()->equals($projectId));
        self::assertSame('2026-07-22T10:00:00+00:00', $stored->getCreatedAt()->format(\DateTimeInterface::ATOM));

        // Creating a donation records no aggregate event — only the Stripe webhook
        // lifecycle (paid/failed/cancelled) does.
        self::assertSame([], $this->eventBus->getDispatchedEvents());
    }

    #[DataProvider('boundaryAmounts')]
    public function test_should_accept_amounts_at_the_exact_bounds(int $amountCents): void
    {
        $projectId = Uuid::v7();
        $this->projectChecker->activate($projectId);

        $result = $this->useCase->handle(new CreateDonationIntentCommand(
            solidarityProjectId: $projectId,
            amountCents: $amountCents,
        ));

        self::assertSame('pi_donation_1', $result->paymentIntentId);
        self::assertSame($amountCents, $this->gateway->calls[0]['amountCents']);
        self::assertSame($amountCents, $this->repository->findByPaymentIntentId('pi_donation_1')->getAmountCents());
    }

    public static function boundaryAmounts(): \Generator
    {
        yield 'minimum 1 euro' => [100];
        yield 'maximum 10000 euros' => [1_000_000];
    }

    #[DataProvider('invalidAmounts')]
    public function test_should_reject_out_of_bounds_amounts_before_calling_the_gateway(int $amountCents, string $expectedMessage): void
    {
        $projectId = Uuid::v7();
        $this->projectChecker->activate($projectId);

        $this->expectException(InvalidDonationException::class);
        $this->expectExceptionMessage($expectedMessage);

        try {
            $this->useCase->handle(new CreateDonationIntentCommand(
                solidarityProjectId: $projectId,
                amountCents: $amountCents,
            ));
        } finally {
            self::assertCount(0, $this->gateway->calls);
            self::assertSame([], $this->repository->all());
        }
    }

    public static function invalidAmounts(): \Generator
    {
        yield 'below minimum' => [99, 'Donation amount must be at least 100 cents (1 euro), got 99 cents.'];
        yield 'zero' => [0, 'Donation amount must be at least 100 cents (1 euro), got 0 cents.'];
        yield 'negative' => [-500, 'Donation amount must be at least 100 cents (1 euro), got -500 cents.'];
        yield 'above maximum' => [1_000_001, 'Donation amount must not exceed 1000000 cents (10000 euros), got 1000001 cents.'];
    }

    public function test_should_reject_when_solidarity_project_is_unknown_or_inactive(): void
    {
        $projectId = Uuid::fromString('01981e2f-dead-7000-beef-0000000000ff');

        $this->expectException(InvalidDonationException::class);
        $this->expectExceptionMessage(\sprintf('Solidarity project "%s" does not exist or does not accept donations.', $projectId->toRfc4122()));

        try {
            $this->useCase->handle(new CreateDonationIntentCommand(
                solidarityProjectId: $projectId,
                amountCents: 2500,
            ));
        } finally {
            self::assertCount(0, $this->gateway->calls);
            self::assertSame([], $this->repository->all());
        }
    }
}
