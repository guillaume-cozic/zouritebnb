<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Notification\Domain\Port\EmailRenderer;

/**
 * Deterministic stand-in for the Twig renderer: echoes the template name and the variable
 * values so unit tests can assert on what was rendered without booting Twig.
 */
final class FakeEmailRenderer implements EmailRenderer
{
    public function renderHtml(string $template, array $variables): string
    {
        return $template.' '.json_encode($variables, \JSON_UNESCAPED_UNICODE | \JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
