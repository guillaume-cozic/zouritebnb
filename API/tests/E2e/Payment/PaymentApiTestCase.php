<?php

declare(strict_types=1);

namespace App\Tests\E2e\Payment;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Payment\Domain\Port\PaymentGateway;
use App\Tests\E2e\AuthenticatedClientTrait;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;

abstract class PaymentApiTestCase extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * Boots the test client with the real Stripe gateway swapped for a deterministic
     * {@see FakePaymentGateway}, so the use case never performs a real HTTP call to Stripe.
     *
     * The fake is returned so individual tests can inspect the recorded gateway calls.
     */
    protected function createClientWithFakeGateway(FakePaymentGateway $gateway): Client
    {
        $client = self::createClient();

        self::getContainer()->set(PaymentGateway::class, $gateway);

        return $client;
    }
}
