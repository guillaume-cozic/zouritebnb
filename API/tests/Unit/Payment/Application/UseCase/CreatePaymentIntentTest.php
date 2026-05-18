<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\UseCase;

use App\Payment\Application\UseCase\CreatePaymentIntent;
use App\Payment\Domain\Command\CreatePaymentIntentCommand;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Exception\InvalidPaymentException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use App\Tests\Unit\Payment\Infrastructure\FixedClock;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreatePaymentIntentTest extends TestCase
{
    private InMemoryPaymentRepository $repository;
    private FakePaymentGateway $gateway;
    private InMemoryEventBus $eventBus;
    private FixedClock $clock;
    private CreatePaymentIntent $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->gateway = new FakePaymentGateway();
        $this->eventBus = new InMemoryEventBus();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-17T10:00:00+00:00'));
        $this->useCase = new CreatePaymentIntent($this->repository, $this->gateway, $this->eventBus, $this->clock);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function testShouldRequestAuthorizationAndPersistPendingPayment(): void
    {
        $paymentId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        UuidGenerator::queue([$paymentId]);

        $result = $this->useCase->handle(new CreatePaymentIntentCommand(
            amountCents: 25000,
            currency: 'EUR',
            description: 'Réservation Maison du lagon',
            metadata: ['accommodationId' => 'abc', 'nights' => 5],
        ));

        self::assertSame('pi_test_1', $result->paymentIntentId);
        self::assertSame('pi_test_1_secret_1', $result->clientSecret);

        self::assertCount(1, $this->gateway->calls);
        self::assertSame('createAuthorization', $this->gateway->calls[0]['type']);
        self::assertSame(25000, $this->gateway->calls[0]['amountCents']);
        self::assertSame('EUR', $this->gateway->calls[0]['currency']);
        self::assertSame('Réservation Maison du lagon', $this->gateway->calls[0]['description']);

        $stored = $this->repository->findById($paymentId);
        self::assertNotNull($stored);
        self::assertSame('pi_test_1', $stored->getStripePaymentIntentId());
        self::assertSame(PaymentStatus::Pending, $stored->getStatus());
        self::assertSame(25000, $stored->getAmountCents());
        self::assertSame('eur', $stored->getCurrency());
        self::assertNull($stored->getReservationId());
        self::assertSame('2026-05-17T10:00:00+00:00', $stored->getCreatedAt()->format(\DateTimeInterface::ATOM));
    }

    public function testShouldRejectZeroAmount(): void
    {
        $this->expectException(InvalidPaymentException::class);

        $this->useCase->handle(new CreatePaymentIntentCommand(
            amountCents: 0,
            currency: 'eur',
            description: 'invalid',
        ));
    }

    public function testShouldRejectInvalidCurrency(): void
    {
        $this->expectException(InvalidPaymentException::class);

        $this->useCase->handle(new CreatePaymentIntentCommand(
            amountCents: 1000,
            currency: 'EUROS',
            description: 'invalid',
        ));
    }
}
