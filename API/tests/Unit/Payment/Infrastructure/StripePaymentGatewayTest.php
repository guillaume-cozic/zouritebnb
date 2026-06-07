<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Infrastructure;

use App\Payment\Domain\Port\GatewayAuthorization;
use App\Payment\Infrastructure\Stripe\StripePaymentGateway;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;

final class StripePaymentGatewayTest extends TestCase
{
    private PaymentIntentService&MockObject $paymentIntents;
    private StripeClient $stripeClient;
    private StripePaymentGateway $gateway;

    #[Before]
    public function initGateway(): void
    {
        $this->paymentIntents = $this->createMock(PaymentIntentService::class);

        // StripeClient exposes services through the magic __get accessor
        // (e.g. $client->paymentIntents). We redirect that lookup to our mock.
        $this->stripeClient = $this->createStub(StripeClient::class);
        $this->stripeClient
            ->method('__get')
            ->with('paymentIntents')
            ->willReturn($this->paymentIntents);

        $this->gateway = new StripePaymentGateway($this->stripeClient);
    }

    public function test_should_create_a_manual_capture_authorization(): void
    {
        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'client_secret' => 'pi_123_secret_abc',
        ]);

        $this->paymentIntents
            ->expects(self::once())
            ->method('create')
            ->with([
                'amount' => 12_000,
                'currency' => 'eur',
                'capture_method' => 'manual',
                'description' => 'Reservation #42',
                'metadata' => ['reservationId' => '42'],
                'automatic_payment_methods' => ['enabled' => true],
            ])
            ->willReturn($intent);

        $authorization = $this->gateway->createAuthorization(
            amountCents: 12_000,
            currency: 'EUR',
            description: 'Reservation #42',
            metadata: ['reservationId' => '42'],
        );

        self::assertInstanceOf(GatewayAuthorization::class, $authorization);
        self::assertSame('pi_123', $authorization->paymentIntentId);
        self::assertSame('pi_123_secret_abc', $authorization->clientSecret);
    }

    public function test_should_lowercase_the_currency_before_calling_stripe(): void
    {
        $this->paymentIntents
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn (array $params): bool => 'usd' === $params['currency']))
            ->willReturn(PaymentIntent::constructFrom(['id' => 'pi_1', 'client_secret' => 's']));

        $this->gateway->createAuthorization(100, 'USD', 'desc', []);
    }

    public function test_should_fallback_to_empty_string_when_client_secret_is_null(): void
    {
        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_no_secret',
            'client_secret' => null,
        ]);

        $this->paymentIntents->expects(self::once())->method('create')->willReturn($intent);

        $authorization = $this->gateway->createAuthorization(100, 'eur', 'desc', []);

        self::assertSame('pi_no_secret', $authorization->paymentIntentId);
        self::assertSame('', $authorization->clientSecret);
    }

    /**
     * @param array<string, string|int|float|bool|null> $metadata
     * @param array<string, string>                     $expected
     */
    #[DataProvider('metadataProvider')]
    public function test_should_normalize_metadata_to_string_values(array $metadata, array $expected): void
    {
        $this->paymentIntents
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $params) use ($expected): bool {
                self::assertSame($expected, $params['metadata']);

                return true;
            }))
            ->willReturn(PaymentIntent::constructFrom(['id' => 'pi_1', 'client_secret' => 's']));

        $this->gateway->createAuthorization(100, 'eur', 'desc', $metadata);
    }

    /**
     * @return \Generator<string, array{array<string, string|int|float|bool|null>, array<string, string>}>
     */
    public static function metadataProvider(): \Generator
    {
        yield 'string value is kept' => [['a' => 'foo'], ['a' => 'foo']];
        yield 'int value is cast to string' => [['a' => 42], ['a' => '42']];
        yield 'float value is cast to string' => [['a' => 1.5], ['a' => '1.5']];
        yield 'bool true becomes 1' => [['a' => true], ['a' => '1']];
        yield 'bool false becomes 0' => [['a' => false], ['a' => '0']];
        yield 'null value is dropped' => [['a' => null, 'b' => 'kept'], ['b' => 'kept']];
        yield 'empty metadata stays empty' => [[], []];
        yield 'mixed values' => [
            ['s' => 'x', 'i' => 7, 'b' => true, 'n' => null],
            ['s' => 'x', 'i' => '7', 'b' => '1'],
        ];
    }

    public function test_should_propagate_stripe_exception_on_create(): void
    {
        $this->paymentIntents
            ->expects(self::once())
            ->method('create')
            ->willThrowException(CardException::factory('Your card was declined.'));

        $this->expectException(CardException::class);
        $this->expectExceptionMessage('Your card was declined.');

        $this->gateway->createAuthorization(100, 'eur', 'desc', []);
    }

    public function test_should_capture_the_payment_intent(): void
    {
        $this->paymentIntents
            ->expects(self::once())
            ->method('capture')
            ->with('pi_capture');

        $this->gateway->capture('pi_capture');
    }

    public function test_should_propagate_stripe_exception_on_capture(): void
    {
        $this->paymentIntents
            ->expects(self::once())
            ->method('capture')
            ->willThrowException(InvalidRequestException::factory('Intent already captured.'));

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Intent already captured.');

        $this->gateway->capture('pi_capture');
    }

    public function test_should_cancel_the_payment_intent(): void
    {
        $this->paymentIntents
            ->expects(self::once())
            ->method('cancel')
            ->with('pi_cancel');

        $this->gateway->cancel('pi_cancel');
    }

    public function test_should_propagate_stripe_exception_on_cancel(): void
    {
        $this->paymentIntents
            ->expects(self::once())
            ->method('cancel')
            ->willThrowException(InvalidRequestException::factory('Intent cannot be canceled.'));

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Intent cannot be canceled.');

        $this->gateway->cancel('pi_cancel');
    }
}
