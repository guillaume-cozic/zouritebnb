<?php

declare(strict_types=1);

namespace App\Tests\E2e\Payment;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Payment\Domain\Port\PaymentGateway;
use App\Payment\Infrastructure\Doctrine\PaymentEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use App\Tests\Unit\Payment\Infrastructure\FakePaymentGateway;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

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

    /**
     * Persists a Payment row and returns its UUID (RFC4122).
     */
    protected function insertPayment(
        string $paymentIntentId,
        string $status = 'pending',
        ?string $reservationId = null,
        int $amountCents = 40000,
        string $currency = 'eur',
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();

        $entity = new PaymentEntity()
            ->setId($id)
            ->setStripePaymentIntentId($paymentIntentId)
            ->setStatus($status)
            ->setReservationId(null === $reservationId ? null : Uuid::fromString($reservationId))
            ->setAmountCents($amountCents)
            ->setCurrency($currency)
            ->setCreatedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    /**
     * Reads back the persisted status of a payment, fresh from the database
     * (the identity map is cleared first so we observe the request's side effects).
     */
    protected function paymentStatus(string $paymentIntentId): ?string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        $entity = $em->getRepository(PaymentEntity::class)
            ->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);

        return $entity?->getStatus();
    }

    /**
     * Builds a minimal, deserializable Stripe event body. The nested `object.object`
     * field drives which Stripe SDK class the payload is hydrated into — `payment_intent`
     * yields a {@see \Stripe\PaymentIntent}, the only shape the webhook acts upon.
     */
    protected function stripeEventPayload(
        string $type,
        string $paymentIntentId,
        string $objectType = 'payment_intent',
    ): string {
        return json_encode([
            'id' => 'evt_test_webhook',
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => ['id' => $paymentIntentId, 'object' => $objectType]],
        ], \JSON_THROW_ON_ERROR);
    }

    /**
     * Produces the headers Stripe would send for a genuine webhook: a `Stripe-Signature`
     * HMAC computed with the same secret the verifier is configured with, so the request
     * passes signature verification exactly like a real Stripe call.
     *
     * @return array{Content-Type: string, Stripe-Signature: string}
     */
    protected function signedStripeWebhookHeaders(string $payload): array
    {
        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? $_SERVER['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_replace_me';
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, (string) $secret);

        return [
            'Content-Type' => 'application/json',
            'Stripe-Signature' => \sprintf('t=%d,v1=%s', $timestamp, $signature),
        ];
    }
}
