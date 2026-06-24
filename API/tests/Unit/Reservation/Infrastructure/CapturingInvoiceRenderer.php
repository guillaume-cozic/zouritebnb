<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Invoice;
use App\Reservation\Domain\Port\InvoiceRenderer;

/** Test double that captures the rendered invoice and returns placeholder bytes. */
final class CapturingInvoiceRenderer implements InvoiceRenderer
{
    public ?Invoice $lastInvoice = null;

    public function render(Invoice $invoice): string
    {
        $this->lastInvoice = $invoice;

        return '%PDF-1.4 fake';
    }
}
