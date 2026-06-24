import { jsPDF } from 'jspdf';
import { Reservation } from './ReservationTypes';

// Kept in sync with the booking breakdown (ReservationConfirmationPage /
// AccommodationDetailPage). The persisted total already includes these, so the
// invoice reconstructs the breakdown from the total to always sum back to it.
const PLATFORM_COMMISSION_RATE = 0.08;
const SOLIDARITY_RATE = 0.07;

const BRAND = 'ZouriteBnb';

export interface InvoiceContext {
  reservation: Reservation;
  accommodationTitle?: string | null;
  accommodationCity?: string | null;
  hostName?: string | null;
  guestName?: string | null;
  /** i18n language ('fr'/'en') — drives labels and number/date formatting. */
  locale: string;
}

interface Labels {
  invoice: string;
  number: string;
  date: string;
  issuedBy: string;
  billedTo: string;
  stay: string;
  host: string;
  dates: string;
  nights: string;
  description: string;
  amount: string;
  accommodation: string;
  serviceFee: string;
  donation: string;
  totalPaid: string;
  footer: string;
  fileName: string;
}

const LABELS: Record<'fr' | 'en', Labels> = {
  fr: {
    invoice: 'FACTURE',
    number: 'Facture n°',
    date: 'Date',
    issuedBy: 'Émise par',
    billedTo: 'Facturé à',
    stay: 'Séjour',
    host: 'Hôte',
    dates: 'Dates',
    nights: 'nuits',
    description: 'Description',
    amount: 'Montant',
    accommodation: 'Hébergement',
    serviceFee: 'Frais de service',
    donation: 'Don solidaire',
    totalPaid: 'Total payé',
    footer: 'Merci pour votre réservation. Paiement réglé via la plateforme.',
    fileName: 'facture',
  },
  en: {
    invoice: 'INVOICE',
    number: 'Invoice no.',
    date: 'Date',
    issuedBy: 'Issued by',
    billedTo: 'Billed to',
    stay: 'Stay',
    host: 'Host',
    dates: 'Dates',
    nights: 'nights',
    description: 'Description',
    amount: 'Amount',
    accommodation: 'Accommodation',
    serviceFee: 'Service fee',
    donation: 'Solidarity donation',
    totalPaid: 'Total paid',
    footer: 'Thank you for your booking. Payment settled through the platform.',
    fileName: 'invoice',
  },
};

const nightsBetween = (checkIn: string, checkOut: string): number => {
  const ms = new Date(checkOut).getTime() - new Date(checkIn).getTime();
  return Math.max(1, Math.round(ms / 86_400_000));
};

const invoiceNumber = (reservationId: string): string =>
  `FAC-${reservationId.replace(/-/g, '').slice(0, 10).toUpperCase()}`;

/**
 * Generate and download a one-page PDF invoice for a (paid) reservation, built
 * entirely client-side from the authoritative API data already loaded in the
 * messaging page. The persisted total is the source of truth; the line items are
 * reconstructed from it so they always add up to what the guest actually paid.
 */
export const generateInvoicePdf = (ctx: InvoiceContext): void => {
  const { reservation, accommodationTitle, accommodationCity, hostName, guestName, locale } = ctx;
  const lang = locale.startsWith('fr') ? 'fr' : 'en';
  const intlLocale = lang === 'fr' ? 'fr-FR' : 'en-GB';
  const l = LABELS[lang];

  const money = (n: number): string =>
    `${n.toLocaleString(intlLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €`;
  const formatDate = (iso: string): string =>
    new Intl.DateTimeFormat(intlLocale, { day: '2-digit', month: 'long', year: 'numeric' }).format(
      new Date(iso)
    );

  const nights = nightsBetween(reservation.checkIn, reservation.checkOut);
  const total =
    reservation.totalPrice ??
    (reservation.pricePerNight ?? 0) * nights * (1 + PLATFORM_COMMISSION_RATE + SOLIDARITY_RATE);
  const subtotal = total / (1 + PLATFORM_COMMISSION_RATE + SOLIDARITY_RATE);
  const serviceFee = subtotal * PLATFORM_COMMISSION_RATE;
  const donation = subtotal * SOLIDARITY_RATE;

  const doc = new jsPDF({ unit: 'pt', format: 'a4' });
  const pageWidth = doc.internal.pageSize.getWidth();
  const left = 48;
  const right = pageWidth - 48;
  let y = 56;

  // Header: brand + invoice meta
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(22);
  doc.setTextColor(17, 24, 39);
  doc.text(BRAND, left, y);

  doc.setFontSize(20);
  doc.setTextColor(79, 70, 229);
  doc.text(l.invoice, right, y, { align: 'right' });

  y += 24;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.setTextColor(107, 114, 128);
  doc.text(`${l.number} ${invoiceNumber(reservation.id)}`, right, y, { align: 'right' });
  y += 14;
  doc.text(`${l.date} : ${formatDate(new Date().toISOString())}`, right, y, { align: 'right' });

  // Parties
  y += 36;
  const partyTop = y;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(107, 114, 128);
  doc.text(l.issuedBy.toUpperCase(), left, y);
  doc.text(l.billedTo.toUpperCase(), left + 270, y);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(11);
  doc.setTextColor(17, 24, 39);
  doc.text(BRAND, left, y + 16);
  doc.text(guestName || reservation.guestName, left + 270, y + 16);

  // Stay summary
  y = partyTop + 56;
  doc.setDrawColor(229, 231, 235);
  doc.line(left, y, right, y);
  y += 24;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.setTextColor(17, 24, 39);
  doc.text(l.stay, left, y);
  y += 18;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.setTextColor(55, 65, 81);
  const stayName = [accommodationTitle, accommodationCity].filter(Boolean).join(' · ');
  if (stayName) {
    doc.text(stayName, left, y);
    y += 14;
  }
  if (hostName) {
    doc.text(`${l.host} : ${hostName}`, left, y);
    y += 14;
  }
  doc.text(
    `${l.dates} : ${formatDate(reservation.checkIn)} → ${formatDate(reservation.checkOut)} (${nights} ${l.nights})`,
    left,
    y
  );

  // Line items table
  y += 30;
  const rowH = 22;
  doc.setFillColor(243, 244, 246);
  doc.rect(left, y - 14, right - left, rowH, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(107, 114, 128);
  doc.text(l.description.toUpperCase(), left + 10, y);
  doc.text(l.amount.toUpperCase(), right - 10, y, { align: 'right' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.setTextColor(31, 41, 55);

  const rows: Array<[string, number]> = [
    [`${l.accommodation} (${money(subtotal / nights)} × ${nights})`, subtotal],
    [`${l.serviceFee} (${Math.round(PLATFORM_COMMISSION_RATE * 100)}%)`, serviceFee],
    [`${l.donation} (${Math.round(SOLIDARITY_RATE * 100)}%)`, donation],
  ];
  rows.forEach(([label, value]) => {
    y += rowH + 4;
    doc.text(label, left + 10, y);
    doc.text(money(value), right - 10, y, { align: 'right' });
  });

  // Total
  y += rowH + 4;
  doc.setDrawColor(229, 231, 235);
  doc.line(left, y - 14, right, y - 14);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.setTextColor(17, 24, 39);
  doc.text(l.totalPaid, left + 10, y + 4);
  doc.text(money(total), right - 10, y + 4, { align: 'right' });

  // Footer
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9);
  doc.setTextColor(156, 163, 175);
  doc.text(l.footer, left, doc.internal.pageSize.getHeight() - 48);

  doc.save(`${l.fileName}-${invoiceNumber(reservation.id)}.pdf`);
};
