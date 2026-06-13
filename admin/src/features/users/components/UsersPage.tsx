import { useCallback } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchUsers } from '../UsersSlice';
import {
  selectUsers,
  selectUsersCount,
  selectUsersError,
  selectUsersPage,
  selectUsersPerPage,
  selectUsersStatus,
} from '../UsersSelectors';
import type { AdminPlatformUser } from '../UsersTypes';
import { Badge, BadgeVariant } from '../../../components/ui/Badge';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';
import { ListPage } from '../../../components/ListPage';
import { useCollectionQuery, type CollectionQuery } from '../../../hooks/useCollectionQuery';

const ROLE_OPTIONS = [
  { value: 'all', label: 'Tous' },
  { value: 'hosts', label: 'Hôtes' },
  { value: 'travelers', label: 'Voyageurs' },
];

const VERIFICATION_LABELS: Record<string, string> = {
  verified: 'Vérifié',
  pending: 'En attente',
  rejected: 'Rejeté',
  unverified: 'Non vérifié',
};

const verificationVariant = (status: string): BadgeVariant => {
  switch (status) {
    case 'verified':
      return 'success';
    case 'pending':
      return 'warning';
    case 'rejected':
      return 'danger';
    default:
      return 'surface';
  }
};

const fullName = (user: AdminPlatformUser): string =>
  [user.firstName, user.lastName].filter(Boolean).join(' ') || '—';

export function UsersPage() {
  const dispatch = useAppDispatch();
  const users = useAppSelector(selectUsers);
  const status = useAppSelector(selectUsersStatus);
  const error = useAppSelector(selectUsersError);
  const total = useAppSelector(selectUsersCount);
  const page = useAppSelector(selectUsersPage);
  const itemsPerPage = useAppSelector(selectUsersPerPage);

  const fetchPage = useCallback(
    (query: CollectionQuery) => {
      dispatch(fetchUsers({ page: query.page, search: query.search, role: query.filter }));
    },
    [dispatch]
  );

  const { search, filter, onSearchChange, onFilterChange, setPage } = useCollectionQuery(fetchPage);

  return (
    <ListPage
      title="Clients"
      subtitle="Tous les comptes de la plateforme."
      count={total}
      search={search}
      onSearchChange={onSearchChange}
      searchPlaceholder="Rechercher par e-mail ou nom…"
      filterOptions={ROLE_OPTIONS}
      filterValue={filter}
      onFilterChange={onFilterChange}
      status={status}
      error={error}
      isEmpty={users.length === 0}
      emptyMessage="Aucun client ne correspond à votre recherche."
      page={page}
      itemsPerPage={itemsPerPage}
      onPageChange={setPage}
    >
      <Table>
        <THead>
          <TH>Email</TH>
          <TH>Nom</TH>
          <TH>Rôles</TH>
          <TH>Vérification</TH>
          <TH>Hébergements</TH>
          <TH>Réservations</TH>
        </THead>
        <TBody>
          {users.map((u) => (
            <TR key={u.id}>
              <TD>{u.email}</TD>
              <TD>{fullName(u)}</TD>
              <TD>
                <span className="flex flex-wrap gap-1">
                  {u.roles.map((role) => (
                    <Badge key={role} variant={role === 'ROLE_ADMIN' ? 'danger' : 'surface'}>
                      {role.replace(/^ROLE_/, '').toLowerCase()}
                    </Badge>
                  ))}
                </span>
              </TD>
              <TD>
                <Badge variant={verificationVariant(u.verificationStatus)}>
                  {VERIFICATION_LABELS[u.verificationStatus] ?? u.verificationStatus}
                </Badge>
              </TD>
              <TD>{u.accommodationCount}</TD>
              <TD>{u.reservationCount}</TD>
            </TR>
          ))}
        </TBody>
      </Table>
    </ListPage>
  );
}
