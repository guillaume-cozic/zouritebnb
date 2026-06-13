import { useCallback } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReservations } from '../ReservationsSlice';
import {
  selectReservations,
  selectReservationsCount,
  selectReservationsError,
  selectReservationsPage,
  selectReservationsPerPage,
  selectReservationsStatus,
} from '../ReservationsSelectors';
import { Badge, BadgeVariant } from '../../../components/ui/Badge';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';
import { ListPage } from '../../../components/ListPage';
import { useCollectionQuery, type CollectionQuery } from '../../../hooks/useCollectionQuery';
import { formatDate, formatMoney } from '../../../services/format';

const STATUS_OPTIONS = [
  { value: 'all', label: 'Toutes' },
  { value: 'pending', label: 'En attente' },
  { value: 'confirmed', label: 'Confirmées' },
  { value: 'cancelled', label: 'Annulées' },
  { value: 'refused', label: 'Refusées' },
  { value: 'expired', label: 'Expirées' },
];

const STATUS_LABELS: Record<string, string> = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  cancelled: 'Annulée',
  refused: 'Refusée',
  expired: 'Expirée',
};

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
  const total = useAppSelector(selectReservationsCount);
  const page = useAppSelector(selectReservationsPage);
  const itemsPerPage = useAppSelector(selectReservationsPerPage);

  const fetchPage = useCallback(
    (query: CollectionQuery) => {
      dispatch(fetchReservations({ page: query.page, search: query.search, status: query.filter }));
    },
    [dispatch]
  );

  const { search, filter, onSearchChange, onFilterChange, setPage } = useCollectionQuery(fetchPage);

  return (
    <ListPage
      title="Réservations"
      subtitle="Toutes les réservations de la plateforme."
      count={total}
      search={search}
      onSearchChange={onSearchChange}
      searchPlaceholder="Rechercher par voyageur ou hébergement…"
      filterOptions={STATUS_OPTIONS}
      filterValue={filter}
      onFilterChange={onFilterChange}
      status={status}
      error={error}
      isEmpty={reservations.length === 0}
      emptyMessage="Aucune réservation ne correspond à votre recherche."
      page={page}
      itemsPerPage={itemsPerPage}
      onPageChange={setPage}
    >
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
          {reservations.map((r) => (
            <TR key={r.id}>
              <TD>{r.guestName}</TD>
              <TD>{r.accommodationTitle ?? '—'}</TD>
              <TD>{formatDate(r.checkIn)}</TD>
              <TD>{formatDate(r.checkOut)}</TD>
              <TD>
                <Badge variant={statusVariant(r.status)}>{STATUS_LABELS[r.status] ?? r.status}</Badge>
              </TD>
              <TD>{formatMoney(r.totalPrice)}</TD>
            </TR>
          ))}
        </TBody>
      </Table>
    </ListPage>
  );
}
