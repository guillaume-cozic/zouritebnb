import api from '../../services/api';

/** Pulls the PDF filename out of a Content-Disposition header, with a fallback. */
const filenameFromDisposition = (header: string | undefined, fallback: string): string => {
  if (!header) return fallback;
  const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(header);
  return match ? decodeURIComponent(match[1]) : fallback;
};

/**
 * Downloads the server-generated PDF invoice of a reservation. The endpoint is
 * JWT-protected, so the file is fetched as a blob through the authenticated API
 * client (a plain link wouldn't carry the token) and saved via an object URL.
 */
export const downloadReservationInvoice = async (reservationId: string): Promise<void> => {
  const response = await api.get(`/api/reservations/${reservationId}/invoice`, {
    responseType: 'blob',
  });

  const blob = new Blob([response.data], { type: 'application/pdf' });
  const filename = filenameFromDisposition(
    response.headers['content-disposition'],
    `facture-${reservationId.slice(0, 8)}.pdf`
  );

  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
};
