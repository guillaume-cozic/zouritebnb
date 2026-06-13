import { useCallback } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchAccommodations } from '../AccommodationsSlice';
import {
  selectAccommodations,
  selectAccommodationsCount,
  selectAccommodationsError,
  selectAccommodationsPage,
  selectAccommodationsPerPage,
  selectAccommodationsStatus,
} from '../AccommodationsSelectors';
import { Badge, BadgeVariant } from '../../../components/ui/Badge';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';
import { ListPage } from '../../../components/ListPage';
import { useCollectionQuery, type CollectionQuery } from '../../../hooks/useCollectionQuery';
import { formatMoney } from '../../../services/format';

const STATUS_OPTIONS = [
  { value: 'all', label: 'Tous' },
  { value: 'published', label: 'Publiés' },
  { value: 'draft', label: 'Brouillons' },
];

const STATUS_LABELS: Record<string, string> = {
  published: 'Publié',
  draft: 'Brouillon',
};

const statusVariant = (status: string): BadgeVariant =>
  status === 'published' ? 'success' : 'surface';

export function AccommodationsPage() {
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectAccommodations);
  const status = useAppSelector(selectAccommodationsStatus);
  const error = useAppSelector(selectAccommodationsError);
  const total = useAppSelector(selectAccommodationsCount);
  const page = useAppSelector(selectAccommodationsPage);
  const itemsPerPage = useAppSelector(selectAccommodationsPerPage);

  const fetchPage = useCallback(
    (query: CollectionQuery) => {
      dispatch(fetchAccommodations({ page: query.page, search: query.search, status: query.filter }));
    },
    [dispatch]
  );

  const { search, filter, onSearchChange, onFilterChange, setPage } = useCollectionQuery(fetchPage);

  return (
    <ListPage
      title="Hébergements"
      subtitle="Tous les hébergements de la plateforme, brouillons inclus."
      count={total}
      search={search}
      onSearchChange={onSearchChange}
      searchPlaceholder="Rechercher par titre, ville ou hôte…"
      filterOptions={STATUS_OPTIONS}
      filterValue={filter}
      onFilterChange={onFilterChange}
      status={status}
      error={error}
      isEmpty={accommodations.length === 0}
      emptyMessage="Aucun hébergement ne correspond à votre recherche."
      page={page}
      itemsPerPage={itemsPerPage}
      onPageChange={setPage}
    >
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
          {accommodations.map((a) => (
            <TR key={a.id}>
              <TD>{a.title ?? '—'}</TD>
              <TD>{a.city ?? '—'}</TD>
              <TD>{a.hostEmail ?? '—'}</TD>
              <TD>{formatMoney(a.price)}</TD>
              <TD>{a.maxGuests !== null ? `${a.maxGuests} pers.` : '—'}</TD>
              <TD>
                <Badge variant={statusVariant(a.status)}>{STATUS_LABELS[a.status] ?? a.status}</Badge>
              </TD>
            </TR>
          ))}
        </TBody>
      </Table>
    </ListPage>
  );
}
