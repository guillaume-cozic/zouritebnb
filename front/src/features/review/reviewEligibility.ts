import { Reservation } from '../reservation/ReservationTypes';

/**
 * A stay can be reviewed once it is confirmed and the check-out date has passed.
 * Mirrors the backend rule (a confirmed and completed stay must exist).
 */
export const isStayCompleted = (
  reservation: Pick<Reservation, 'status' | 'checkOut'>,
  now: Date = new Date()
): boolean => {
  if (reservation.status !== 'confirmed') return false;
  const checkOut = new Date(reservation.checkOut);
  if (Number.isNaN(checkOut.getTime())) return false;
  return checkOut.getTime() <= now.getTime();
};
