<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\Http;

use App\Payment\Application\UseCase\RecordStripeEvent;
use App\Payment\Domain\Command\RecordStripeEventCommand;
use App\Payment\Infrastructure\Stripe\StripeWebhookSignatureVerifier;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\PaymentIntent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class StripeWebhookController
{
    public function __construct(
        private StripeWebhookSignatureVerifier $signatureVerifier,
        private RecordStripeEvent $recordStripeEvent,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    #[Route('/api/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->headers->get('Stripe-Signature', '');

        try {
            $event = $this->signatureVerifier->verify($payload, $signature);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            return new JsonResponse(
                ['error' => 'Invalid Stripe webhook signature or payload.', 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $object = $event->data->object ?? null;
        if (!$object instanceof PaymentIntent) {
            // Event is not about a PaymentIntent — acknowledge so Stripe stops retrying.
            return new JsonResponse(['received' => true]);
        }

        $this->handler->execute(fn () => $this->recordStripeEvent->handle(new RecordStripeEventCommand(
            eventType: $event->type,
            paymentIntentId: $object->id,
        )));

        return new JsonResponse(['received' => true]);
    }
}
