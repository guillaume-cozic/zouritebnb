import { useEffect, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchAccommodations } from '../AccommodationsSlice';
import {
  selectAccommodations,
  selectAccommodationsError,
  selectAccommodationsStatus,
  selectAccommodationStatuses,
} from '../AccommodationsSelectors';
import { Badge, BadgeVariant } from '../../../components/ui/Badge';
import { ListSkeleton } from '../../../components/ui/Skeleton';
import { ErrorMessage } from '../../../components/ui/ErrorMessage';
import { EmptyState } from '../../../components/ui/EmptyState';
import { SearchInput } from '../../../components/ui/SearchInput';
import { FilterChips } from '../../../components/ui/FilterChips';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';
import { formatMoney } from '../../../services/format';

const statusVariant = (status: string): BadgeVariant => {
  switch (status) {
    case 'published':
      return 'success';
    default:
      return 'surface';
  }
};

export function AccommodationsPage() {
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectAccommodations);
  const status = useAppSelector(selectAccommodationsStatus);
  const error = useAppSelector(selectAccommodationsError);
  const statuses = useAppSelector(selectAccommodationStatuses);

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  useEffect(() => {
    dispatch(fetchAccommodations());
  }, [dispatch]);

  const query = search.trim().toLowerCase();
  const filtered = accommodations.filter((a) => {
    if (statusFilter !== 'all' && a.status !== statusFilter) return false;
    if (!query) return true;
    return (
      (a.title ?? '').toLowerCase().includes(query) ||
      (a.city ?? '').toLowerCase().includes(query) ||
      (a.hostEmail ?? '').toLowerCase().includes(query)
    );
  });

  return (
    <div>
      <h1 className="text-2xl font-bold text-surface-900">Hébergements</h1>

      <div className="mt-6 flex flex-wrap items-center gap-4">
        <SearchInput
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par titre, ville ou hôte…"
        />
        <FilterChips
          options={[
            { value: 'all', label: 'Tous' },
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
          <EmptyState message="Aucun hébergement ne correspond à votre recherche." />
        ) : (
          <Table>
            <THead>
              <TH>Titre</TH>
              <TH>Ville</TH>
              <TH>Hôte</TH>
              <TH>Prix/nuit</TH>
              <TH>Capacité</TH>
              <TH>Statut</TH>
            </THead>
            <TBody>
              {filtered.map((a) => (
                <TR key={a.id}>
                  <TD>{a.title ?? '—'}</TD>
                  <TD>{a.city ?? '—'}</TD>
                  <TD>{a.hostEmail ?? '—'}</TD>
                  <TD>{formatMoney(a.price)}</TD>
                  <TD>{a.maxGuests !== null ? `${a.maxGuests} pers.` : '—'}</TD>
                  <TD>
                    <Badge variant={statusVariant(a.status)}>{a.status}</Badge>
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        )}
      </div>
    </div>
  );
}
