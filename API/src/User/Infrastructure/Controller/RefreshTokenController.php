<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Declares the /api/token/refresh route so Symfony's router resolves the path.
 *
 * The actual exchange is performed by the gesdinet `refresh_jwt` authenticator
 * on the `token_refresh` firewall, which intercepts this path during the
 * security listener and returns the new JWT before this controller ever runs.
 * The body below is only a defensive fallback and should never be reached.
 */
#[AsController]
final readonly class RefreshTokenController
{
    #[Route('/api/token/refresh', name: 'gesdinet_jwt_refresh_token', methods: ['POST'])]
    public function __invoke(): Response
    {
        return new JsonResponse(['message' => 'Missing refresh token.'], Response::HTTP_UNAUTHORIZED);
    }
}
