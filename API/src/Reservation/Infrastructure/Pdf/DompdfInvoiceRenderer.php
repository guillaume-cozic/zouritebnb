<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Pdf;

use App\Reservation\Domain\Entity\Invoice;
use App\Reservation\Domain\Port\InvoiceRenderer;
use Dompdf\Dompdf;
use Dompdf\Options;

/** Renders an {@see Invoice} to a one-page A4 PDF using dompdf. */
final readonly class DompdfInvoiceRenderer implements InvoiceRenderer
{
    public function render(Invoice $invoice): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($this->buildHtml($invoice), 'UTF-8');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    private function buildHtml(Invoice $invoice): string
    {
        $rows = '';
        foreach ($invoice->lines as $line) {
            $rows .= \sprintf(
                '<tr><td class="desc">%s</td><td class="amount">%s</td></tr>',
                $this->esc($line->label),
                $this->money($line->amount, $invoice->currency),
            );
        }

        $stay = array_filter([$invoice->accommodationTitle, $invoice->accommodationCity]);
        $stayLine = [] !== $stay ? $this->esc(implode(' · ', $stay)) : '';
        $hostLine = null !== $invoice->hostName
            ? '<div>Hôte : '.$this->esc($invoice->hostName).'</div>'
            : '';

        return <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
            <head>
            <meta charset="utf-8">
            <style>
                * { font-family: Helvetica, Arial, sans-serif; }
                body { color: #1f2937; font-size: 12px; margin: 0; }
                .wrap { padding: 40px 48px; }
                .header { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
                .brand { font-size: 22px; font-weight: bold; color: #111827; }
                .title { font-size: 20px; font-weight: bold; color: #4f46e5; text-align: right; }
                .meta { color: #6b7280; font-size: 11px; text-align: right; }
                .parties { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
                .parties td { width: 50%; vertical-align: top; }
                .label { font-size: 9px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
                .party { font-size: 13px; color: #111827; margin-top: 4px; }
                .stay { margin: 20px 0; padding: 14px 16px; background: #f9fafb; border-radius: 8px; color: #374151; }
                .stay .h { font-weight: bold; color: #111827; margin-bottom: 4px; }
                table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
                table.items th { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280;
                    text-align: left; padding: 8px 10px; background: #f3f4f6; }
                table.items th.amount, td.amount { text-align: right; }
                table.items td { padding: 9px 10px; border-bottom: 1px solid #f0f0f0; }
                tr.total td { font-weight: bold; font-size: 14px; color: #111827; border-bottom: none; padding-top: 14px; }
                .footer { margin-top: 36px; color: #9ca3af; font-size: 10px; }
            </style>
            </head>
            <body>
            <div class="wrap">
                <table class="header"><tr>
                    <td class="brand">{$this->esc($invoice->sellerName)}</td>
                    <td>
                        <div class="title">FACTURE</div>
                        <div class="meta">Facture n° {$this->esc($invoice->number)}</div>
                        <div class="meta">Date : {$invoice->issuedAt->format('d/m/Y')}</div>
                    </td>
                </tr></table>

                <table class="parties"><tr>
                    <td>
                        <div class="label">Émise par</div>
                        <div class="party">{$this->esc($invoice->sellerName)}</div>
                    </td>
                    <td>
                        <div class="label">Facturé à</div>
                        <div class="party">{$this->esc($invoice->guestName)}</div>
                    </td>
                </tr></table>

                <div class="stay">
                    <div class="h">Séjour</div>
                    <div>{$stayLine}</div>
                    {$hostLine}
                    <div>Dates : {$invoice->checkIn->format('d/m/Y')} → {$invoice->checkOut->format('d/m/Y')} ({$invoice->nights} nuit{$this->plural($invoice->nights)})</div>
                </div>

                <table class="items">
                    <thead><tr><th>Description</th><th class="amount">Montant</th></tr></thead>
                    <tbody>
                        {$rows}
                        <tr class="total"><td>Total payé</td><td class="amount">{$this->money($invoice->total, $invoice->currency)}</td></tr>
                    </tbody>
                </table>

                <div class="footer">Merci pour votre réservation. Paiement réglé via la plateforme {$this->esc($invoice->sellerName)}.</div>
            </div>
            </body>
            </html>
            HTML;
    }

    private function money(float $amount, string $currency): string
    {
        $symbol = 'EUR' === $currency ? '€' : $currency;

        return number_format($amount, 2, ',', "\u{00A0}")."\u{00A0}".$symbol;
    }

    private function plural(int $n): string
    {
        return $n > 1 ? 's' : '';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
