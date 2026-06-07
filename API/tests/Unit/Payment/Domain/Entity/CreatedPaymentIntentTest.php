<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment\Domain\Entity;

use App\Payment\Domain\Entity\CreatedPaymentIntent;
use PHPUnit\Framework\TestCase;

final class CreatedPaymentIntentTest extends TestCase
{
    public function test_should_expose_payment_intent_id_and_client_secret(): void
    {
        $createdPaymentIntent = new CreatedPaymentIntent(
            paymentIntentId: 'pi_123',
            clientSecret: 'pi_123_secret_abc',
        );

        self::assertSame('pi_123', $createdPaymentIntent->paymentIntentId);
        self::assertSame('pi_123_secret_abc', $createdPaymentIntent->clientSecret);
    }
}
