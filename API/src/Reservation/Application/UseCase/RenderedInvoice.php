<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

/** The downloadable result of generating an invoice: a filename and the PDF bytes. */
final readonly class RenderedInvoice
{
    public function __construct(
        public string $filename,
        public string $content,
    ) {
    }
}
