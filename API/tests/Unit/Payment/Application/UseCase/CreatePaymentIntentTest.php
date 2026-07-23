<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Application\UseCase;

use App\Payment\Application\UseCase\CreatePaymentIntent;
use App\Payment\Domain\Command\CreatePaymentIntentCommand;
use App\Payment\Domain\Entity\PaymentStatus;
use App\Payment\Domain\Exception\InvalidPaymentException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Shared\Domain\Service\StayPriceCalculator;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use App\Tests\Unit\Payment\Infrastructure\FixedClock;
use App\Tests\Unit\Payment\Infrastructure\InMemoryPaymentRepository;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryAccommodationPricingProvider;
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
    private InMemoryAccommodationPricingProvider $pricingProvider;
    private CreatePaymentIntent $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryPaymentRepository();
        $this->gateway = new FakePaymentGateway();
        $this->eventBus = new InMemoryEventBus();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-17T10:00:00+00:00'));
        $this->pricingProvider = new InMemoryAccommodationPricingProvider();
        $this->useCase = new CreatePaymentIntent(
            $this->repository,
            $this->gateway,
            $this->eventBus,
            $this->clock,
            $this->pricingProvider,
            new StayPriceCalculator(),
        );
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_derive_amount_from_pricing_and_persist_pending_payment(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $userId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        $paymentId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        UuidGenerator::queue([$paymentId]);

        // 100€/night, 4 nights, no promotion => 400€ = 40000 cents.
        $this->pricingProvider->set($accommodationId, 100.0);

        $result = $this->useCase->handle(new CreatePaymentIntentCommand(
            accommodationId: $accommodationId,
            checkIn: new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
            checkOut: new \DateTimeImmutable('2026-06-14T11:00:00+00:00'),
            userId: $userId,
        ));

        self::assertSame('pi_test_1', $result->paymentIntentId);

        self::assertCount(1, $this->gateway->calls);
        self::assertSame(40000, $this->gateway->calls[0]['amountCents']);
        self::assertSame('eur', $this->gateway->calls[0]['currency']);
        self::assertSame($accommodationId->toRfc4122(), $this->gateway->calls[0]['metadata']['accommodationId']);
        self::assertSame($userId->toRfc4122(), $this->gateway->calls[0]['metadata']['userId']);

        $stored = $this->repository->findById($paymentId);
        self::assertNotNull($stored);
        self::assertSame(PaymentStatus::Pending, $stored->getStatus());
        self::assertSame(40000, $stored->getAmountCents());
        self::assertSame('eur', $stored->getCurrency());
        self::assertNull($stored->getReservationId());
    }

    public function test_should_apply_weekly_promotion_for_stays_of_seven_nights_or_more(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a2');
        UuidGenerator::queue([Uuid::fromString('01961e2f-dead-7000-beef-000000000002')]);

        // 100€/night, 7 nights, 20% promotion => 80€ × 7 = 560€ = 56000 cents.
        $this->pricingProvider->set($accommodationId, 100.0, 20.0);

        $this->useCase->handle(new CreatePaymentIntentCommand(
            accommodationId: $accommodationId,
            checkIn: new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
            checkOut: new \DateTimeImmutable('2026-06-17T11:00:00+00:00'),
            userId: Uuid::v7(),
        ));

        self::assertSame(56000, $this->gateway->calls[0]['amountCents']);
    }

    public function test_should_include_extra_services_billed_with_the_reservation_in_the_amount(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a4');
        UuidGenerator::queue([Uuid::fromString('01961e2f-dead-7000-beef-000000000003')]);

        // 100€/night × 4 nights = 400€, plus 30€ of extra services billed
        // once per stay => 430€ = 43000 cents charged on Stripe.
        $this->pricingProvider->set($accommodationId, 100.0, billedExtraServices: [
            ['name' => 'Ménage', 'price' => 30.0],
        ]);

        $this->useCase->handle(new CreatePaymentIntentCommand(
            accommodationId: $accommodationId,
            checkIn: new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
            checkOut: new \DateTimeImmutable('2026-06-14T11:00:00+00:00'),
            userId: Uuid::v7(),
        ));

        self::assertSame(43000, $this->gateway->calls[0]['amountCents']);
    }

    public function test_should_reject_when_accommodation_not_found(): void
    {
        $this->expectException(InvalidPaymentException::class);

        try {
            $this->useCase->handle(new CreatePaymentIntentCommand(
                accommodationId: Uuid::v7(),
                checkIn: new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
                checkOut: new \DateTimeImmutable('2026-06-14T11:00:00+00:00'),
                userId: Uuid::v7(),
            ));
        } finally {
            self::assertCount(0, $this->gateway->calls);
        }
    }

    public function test_should_reject_a_zero_night_stay_before_calling_the_gateway(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a3');
        $this->pricingProvider->set($accommodationId, 100.0);

        $this->expectException(InvalidPaymentException::class);

        try {
            $this->useCase->handle(new CreatePaymentIntentCommand(
                accommodationId: $accommodationId,
                checkIn: new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
                checkOut: new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
                userId: Uuid::v7(),
            ));
        } finally {
            self::assertCount(0, $this->gateway->calls);
        }
    }
}
