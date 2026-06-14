<?php

declare(strict_types=1);

namespace App\Tests\E2e\Payment;

/**
 * Exercises the full webhook flow with a *valid* signature: HTTP → signature verification
 * → RecordStripeEvent → Payment status transition → persistence.
 *
 * Signature rejection (400) lives in {@see StripeWebhookTest}; here every request is
 * correctly signed, so we assert on the resulting payment status instead.
 */
final class ProcessStripeWebhookTest extends PaymentApiTestCase
{
    public function test_should_capture_payment_when_payment_intent_succeeded(): void
    {
        $client = self::createClient();
        $this->insertPayment('pi_webhook_1', status: 'authorized');

        $payload = $this->stripeEventPayload('payment_intent.succeeded', 'pi_webhook_1');
        $client->request('POST', '/api/stripe/webhook', [
            'headers' => $this->signedStripeWebhookHeaders($payload),
            'body' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['received' => true]);
        self::assertSame('captured', $this->paymentStatus('pi_webhook_1'));
    }

    public function test_should_authorize_payment_when_amount_becomes_capturable(): void
    {
        $client = self::createClient();
        $this->insertPayment('pi_webhook_2', status: 'pending');

        $payload = $this->stripeEventPayload('payment_intent.amount_capturable_updated', 'pi_webhook_2');
        $client->request('POST', '/api/stripe/webhook', [
            'headers' => $this->signedStripeWebhookHeaders($payload),
            'body' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('authorized', $this->paymentStatus('pi_webhook_2'));
    }

    public function test_should_fail_payment_when_payment_intent_fails(): void
    {
        $client = self::createClient();
        $this->insertPayment('pi_webhook_3', status: 'authorized');

        $payload = $this->stripeEventPayload('payment_intent.payment_failed', 'pi_webhook_3');
        $client->request('POST', '/api/stripe/webhook', [
            'headers' => $this->signedStripeWebhookHeaders($payload),
            'body' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('failed', $this->paymentStatus('pi_webhook_3'));
    }

    public function test_should_cancel_payment_when_payment_intent_canceled(): void
    {
        $client = self::createClient();
        $this->insertPayment('pi_webhook_4', status: 'authorized');

        $payload = $this->stripeEventPayload('payment_intent.canceled', 'pi_webhook_4');
        $client->request('POST', '/api/stripe/webhook', [
            'headers' => $this->signedStripeWebhookHeaders($payload),
            'body' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('cancelled', $this->paymentStatus('pi_webhook_4'));
    }

    public function test_should_acknowledge_and_leave_status_untouched_for_unhandled_event_type(): void
    {
        $client = self::createClient();
        $this->insertPayment('pi_webhook_5', status: 'pending');

        // payment_intent.created is a real Stripe event the domain does not act upon.
        $payload = $this->stripeEventPayload('payment_intent.created', 'pi_webhook_5');
        $client->request('POST', '/api/stripe/webhook', [
            'headers' => $this->signedStripeWebhookHeaders($payload),
            'body' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['received' => true]);
        self::assertSame('pending', $this->paymentStatus('pi_webhook_5'));
    }

    public function test_should_acknowledge_and_ignore_events_that_are_not_about_a_payment_intent(): void
    {
        $client = self::createClient();
        // A payment row shares the id, but the event carries a charge object, not a PaymentIntent.
        $this->insertPayment('pi_webhook_6', status: 'authorized');

        $payload = $this->stripeEventPayload('charge.refunded', 'pi_webhook_6', objectType: 'charge');
        $client->request('POST', '/api/stripe/webhook', [
            'headers' => $this->signedStripeWebhookHeaders($payload),
            'body' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['received' => true]);
        // The non-PaymentIntent event must never mutate the payment.
        self::assertSame('authorized', $this->paymentStatus('pi_webhook_6'));
    }

    public function test_should_acknowledge_when_no_local_payment_matches_the_intent(): void
    {
        $client = self::createClient();

        // No Payment row exists for this intent (webhook/local-row race): acknowledge so
        // Stripe stops retrying, without erroring.
        $payload = $this->stripeEventPayload('payment_intent.succeeded', 'pi_unknown_intent');
        $client->request('POST', '/api/stripe/webhook', [
            'headers' => $this->signedStripeWebhookHeaders($payload),
            'body' => $payload,
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['received' => true]);
    }
}
