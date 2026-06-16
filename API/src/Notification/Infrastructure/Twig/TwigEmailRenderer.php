<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Twig;

use App\Notification\Domain\Port\EmailRenderer;
use Twig\Environment;

final readonly class TwigEmailRenderer implements EmailRenderer
{
    public function __construct(private Environment $twig)
    {
    }

    public function renderHtml(string $template, array $variables): string
    {
        return $this->twig->render($template, $variables);
    }
}
