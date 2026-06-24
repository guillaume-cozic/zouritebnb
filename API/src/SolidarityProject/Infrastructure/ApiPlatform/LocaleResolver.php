<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use App\SolidarityProject\Domain\Entity\SolidarityProject;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the locale to serve solidarity project content in, from the request's
 * Accept-Language header, restricted to the supported locales and defaulting to
 * the platform default locale when none matches.
 */
final readonly class LocaleResolver
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function current(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return SolidarityProject::DEFAULT_LOCALE;
        }

        // getPreferredLanguage() returns the first supported locale when the header
        // is absent or matches nothing, so the result is always a supported locale.
        return $request->getPreferredLanguage(SolidarityProject::SUPPORTED_LOCALES)
            ?? SolidarityProject::DEFAULT_LOCALE;
    }
}
