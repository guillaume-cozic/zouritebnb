<?php

declare(strict_types=1);

namespace App\Notification\Domain\Port;

interface EmailRenderer
{
    /**
     * Renders an HTML email body from a view (template) and its variables.
     *
     * @param array<string, mixed> $variables
     */
    public function renderHtml(string $template, array $variables): string;
}
