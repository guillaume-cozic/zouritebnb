import { useEffect, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReservations } from '../ReservationsSlice';
import {
  selectReservations,
  selectReservationsError,
  selectReservationsStatus,
  selectReservationStatuses,
} from '../ReservationsSelectors';
import { Badge, BadgeVariant } from '../../../components/ui/Badge';
import { ListSkeleton } from '../../../components/ui/Skeleton';
import { ErrorMessage } from '../../../components/ui/ErrorMessage';
import { EmptyState } from '../../../components/ui/EmptyState';
import { SearchInput } from '../../../components/ui/SearchInput';
import { FilterChips } from '../../../components/ui/FilterChips';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';
import { formatDate, formatMoney } from '../../../services/format';

const statusVariant = (status: string): BadgeVariant => {
  switch (status) {
    case 'confirmed':
      return 'success';
    case 'pending':
      return 'warning';
    case 'cancelled':
    case 'refused':
      return 'danger';
    default:
      return 'surface';
  }
};

export function ReservationsPage() {
  const dispatch = useAppDispatch();
  const reservations = useAppSelector(selectReservations);
  const status = useAppSelector(selectReservationsStatus);
  const error = useAppSelector(selectReservationsError);
  const statuses = useAppSelector(selectReservationStatuses);

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  useEffect(() => {
    dispatch(fetchReservations());
  }, [dispatch]);

  const query = search.trim().toLowerCase();
  const filtered = reservations.filter((r) => {
    if (statusFilter !== 'all' && r.status !== statusFilter) return false;
    if (!query) return true;
    return (
      r.guestName.toLowerCase().includes(query) ||
      (r.accommodationTitle ?? '').toLowerCase().includes(query)
    );
  });

  return (
    <div>
      <h1 className="text-2xl font-bold text-surface-900">Réservations</h1>

      <div className="mt-6 flex flex-wrap items-center gap-4">
        <SearchInput
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par voyageur ou hébergement…"
        />
        <FilterChips
          options={[
            { value: 'all', label: 'Toutes' },
            ...statuses.map((s) => ({ value: s, label: s })),
          ]}
          value={statusFilter}
          onChange={setStatusFilter}
        />
      </div>

      <div className="mt-6">
        {status === 'loading' || status === 'idle' ? (
          <ListSkeleton />
        ) : status === 'failed' ? (
          <ErrorMessage message={error} />
        ) : filtered.length === 0 ? (
          <EmptyState message="Aucune réservation ne correspond à votre recherche." />
        ) : (
          <Table>
            <THead>
              <TH>Voyageur</TH>
              <TH>Hébergement</TH>
              <TH>Arrivée</TH>
              <TH>Départ</TH>
              <TH>Statut</TH>
              <TH>Total</TH>
            </THead>
            <TBody>
              {filtered.map((r) => (
                <TR key={r.id}>
                  <TD>{r.guestName}</TD>
                  <TD>{r.accommodationTitle ?? '—'}</TD>
                  <TD>{formatDate(r.checkIn)}</TD>
                  <TD>{formatDate(r.checkOut)}</TD>
                  <TD>
                    <Badge variant={statusVariant(r.status)}>{r.status}</Badge>
                  </TD>
                  <TD>{formatMoney(r.totalPrice)}</TD>
                </TR>
              ))}
            </TBody>
          </Table>
        )}
      </div>
    </div>
  );
}
