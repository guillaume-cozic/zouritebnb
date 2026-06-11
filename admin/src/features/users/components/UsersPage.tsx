import { useEffect, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchUsers } from '../UsersSlice';
import { selectUsers, selectUsersError, selectUsersStatus } from '../UsersSelectors';
import type { AdminPlatformUser } from '../UsersTypes';
import { Badge, BadgeVariant } from '../../../components/ui/Badge';
import { ListSkeleton } from '../../../components/ui/Skeleton';
import { ErrorMessage } from '../../../components/ui/ErrorMessage';
import { EmptyState } from '../../../components/ui/EmptyState';
import { SearchInput } from '../../../components/ui/SearchInput';
import { FilterChips } from '../../../components/ui/FilterChips';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';

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

  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');

  useEffect(() => {
    dispatch(fetchUsers());
  }, [dispatch]);

  const query = search.trim().toLowerCase();
  const filtered = users.filter((u) => {
    if (roleFilter === 'hosts' && u.accommodationCount === 0) return false;
    if (roleFilter === 'travelers' && u.accommodationCount > 0) return false;
    if (!query) return true;
    return u.email.toLowerCase().includes(query) || fullName(u).toLowerCase().includes(query);
  });

  return (
    <div>
      <h1 className="text-2xl font-bold text-surface-900">Clients</h1>

      <div className="mt-6 flex flex-wrap items-center gap-4">
        <SearchInput value={search} onChange={setSearch} placeholder="Rechercher par e-mail ou nom…" />
        <FilterChips
          options={[
            { value: 'all', label: 'Tous' },
            { value: 'hosts', label: 'Hôtes' },
            { value: 'travelers', label: 'Voyageurs' },
          ]}
          value={roleFilter}
          onChange={setRoleFilter}
        />
      </div>

      <div className="mt-6">
        {status === 'loading' || status === 'idle' ? (
          <ListSkeleton />
        ) : status === 'failed' ? (
          <ErrorMessage message={error} />
        ) : filtered.length === 0 ? (
          <EmptyState message="Aucun client ne correspond à votre recherche." />
        ) : (
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
              {filtered.map((u) => (
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
                      {u.verificationStatus}
                    </Badge>
                  </TD>
                  <TD>{u.accommodationCount}</TD>
                  <TD>{u.reservationCount}</TD>
                </TR>
              ))}
            </TBody>
          </Table>
        )}
      </div>
    </div>
  );
}
