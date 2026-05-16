import { ReservationStatus } from '../ReservationTypes';

export interface EventColor {
  backgroundColor: string;
  borderColor: string;
  textColor: string;
  badgeClass: string;
}

export const colorForStatus = (status: ReservationStatus): EventColor => {
  switch (status) {
    case 'confirmed':
      return {
        backgroundColor: '#86efac',
        borderColor: '#86efac',
        textColor: '#14532d',
        badgeClass: 'bg-green-200 text-green-900',
      };
    case 'cancelled':
      return {
        backgroundColor: '#f3f4f6',
        borderColor: '#f3f4f6',
        textColor: '#6b7280',
        badgeClass: 'bg-gray-200 text-gray-600 opacity-70',
      };
    case 'pending':
    default:
      return {
        backgroundColor: '#fef3c7',
        borderColor: '#fef3c7',
        textColor: '#92400e',
        badgeClass: 'bg-amber-100 text-amber-800',
      };
  }
};
