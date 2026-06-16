<?php

declare(strict_types=1);

namespace App\Notification\Application\Email;

/**
 * A renderable email: the Twig view to use, its subject, and the variables the view needs.
 * The HTML body is produced from the view by the {@see \App\Notification\Domain\Port\EmailRenderer}.
 */
final readonly class EmailView
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $template,
        public string $subject,
        public array $variables,
    ) {
    }
}
