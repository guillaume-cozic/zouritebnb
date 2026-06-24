<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Port;

use App\Reservation\Domain\Entity\Invoice;

interface InvoiceRenderer
{
    /** Renders the invoice as binary PDF content. */
    public function render(Invoice $invoice): string;
}
