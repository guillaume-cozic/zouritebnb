import { useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  fetchSolidarityProjects,
  markSolidarityProjectAsDefault,
  setSolidarityProjectStatus,
} from '../SolidarityProjectsSlice';
import {
  selectSolidarityProjects,
  selectSolidarityProjectsCount,
  selectSolidarityProjectsError,
  selectSolidarityProjectsPage,
  selectSolidarityProjectsPerPage,
  selectSolidarityProjectsStatus,
} from '../SolidarityProjectsSelectors';
import type { AdminSolidarityProject } from '../SolidarityProjectsTypes';
import { Badge } from '../../../components/ui/Badge';
import { Button } from '../../../components/ui/Button';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';
import { ListPage } from '../../../components/ListPage';
import { useCollectionQuery, type CollectionQuery } from '../../../hooks/useCollectionQuery';
import { formatDate } from '../../../services/format';

const STATUS_OPTIONS = [
  { value: 'all', label: 'Tous' },
  { value: 'active', label: 'Actifs' },
  { value: 'closed', label: 'Clôturés' },
];

export function SolidarityProjectsPage() {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const projects = useAppSelector(selectSolidarityProjects);
  const status = useAppSelector(selectSolidarityProjectsStatus);
  const error = useAppSelector(selectSolidarityProjectsError);
  const total = useAppSelector(selectSolidarityProjectsCount);
  const page = useAppSelector(selectSolidarityProjectsPage);
  const itemsPerPage = useAppSelector(selectSolidarityProjectsPerPage);

  const fetchPage = useCallback(
    (query: CollectionQuery) => {
      dispatch(
        fetchSolidarityProjects({ page: query.page, search: query.search, status: query.filter })
      );
    },
    [dispatch]
  );

  const { search, filter, onSearchChange, onFilterChange, setPage, reload } =
    useCollectionQuery(fetchPage);

  const toggleStatus = async (project: AdminSolidarityProject) => {
    const next = project.status === 'active' ? 'closed' : 'active';
    await dispatch(setSolidarityProjectStatus({ id: project.id, status: next }));
    reload();
  };

  const markAsDefault = async (project: AdminSolidarityProject) => {
    await dispatch(markSolidarityProjectAsDefault({ id: project.id }));
    reload();
  };

  return (
    <ListPage
      title="Projets solidaires"
      subtitle="Créez et gérez les projets solidaires de la plateforme."
      count={total}
      search={search}
      onSearchChange={onSearchChange}
      searchPlaceholder="Rechercher par titre ou description…"
      filterOptions={STATUS_OPTIONS}
      filterValue={filter}
      onFilterChange={onFilterChange}
      status={status}
      error={error}
      isEmpty={projects.length === 0}
      emptyMessage="Aucun projet solidaire. Créez-en un avec le bouton « Nouveau projet »."
      page={page}
      itemsPerPage={itemsPerPage}
      onPageChange={setPage}
      headerAction={
        <Button onClick={() => navigate('/solidarity-projects/new')}>+ Nouveau projet</Button>
      }
    >
      <Table>
        <THead>
          <TH>Projet</TH>
          <TH>Statut</TH>
          <TH>Coup de cœur</TH>
          <TH>Créé le</TH>
          <TH />
        </THead>
        <TBody>
          {projects.map((project) => (
            <TR key={project.id}>
              <TD>
                <div className="flex items-center gap-3">
                  {project.imageUrl ? (
                    <img
                      src={project.imageUrl}
                      alt=""
                      className="h-10 w-10 shrink-0 rounded-lg object-cover"
                    />
                  ) : (
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface-100 text-surface-400">
                      ❤
                    </span>
                  )}
                  <span className="font-medium text-surface-900">{project.title ?? '—'}</span>
                </div>
              </TD>
              <TD>
                <Badge variant={project.status === 'active' ? 'success' : 'surface'}>
                  {project.status === 'active' ? 'Actif' : 'Clôturé'}
                </Badge>
              </TD>
              <TD>
                {project.isDefault ? (
                  <Badge variant="primary">Coup de cœur</Badge>
                ) : (
                  <button
                    type="button"
                    onClick={() => markAsDefault(project)}
                    className="text-sm font-medium text-primary-600 hover:text-primary-700"
                  >
                    Définir
                  </button>
                )}
              </TD>
              <TD>{formatDate(project.createdAt ?? '')}</TD>
              <TD>
                <div className="flex items-center justify-end gap-2">
                  <Button
                    variant="secondary"
                    size="sm"
                    onClick={() => navigate(`/solidarity-projects/${project.id}/edit`)}
                  >
                    Modifier
                  </Button>
                  <Button
                    variant={project.status === 'active' ? 'warning' : 'success'}
                    size="sm"
                    onClick={() => toggleStatus(project)}
                  >
                    {project.status === 'active' ? 'Désactiver' : 'Activer'}
                  </Button>
                </div>
              </TD>
            </TR>
          ))}
        </TBody>
      </Table>
    </ListPage>
  );
}
