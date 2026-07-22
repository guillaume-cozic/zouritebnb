import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  deleteActivityPoint,
  fetchActivityPoints,
  fetchAllActivityPoints,
  updateActivityPoint,
} from '../ActivityPointsSlice';
import {
  selectActivityPoints,
  selectActivityPointsCount,
  selectActivityPointsError,
  selectActivityPointsMapError,
  selectActivityPointsMapItems,
  selectActivityPointsMapStatus,
  selectActivityPointsPage,
  selectActivityPointsPerPage,
  selectActivityPointsStatus,
  selectActivityPointSaveError,
  selectActivityPointSaveState,
} from '../ActivityPointsSelectors';
import type { ActivityPoint } from '../ActivityPointsTypes';
import { CATEGORY_META } from '../ActivityPointsTypes';
import { Badge } from '../../../components/ui/Badge';
import { Button } from '../../../components/ui/Button';
import { Table, TBody, TD, TH, THead, TR } from '../../../components/ui/Table';
import { PageHeader } from '../../../components/ui/Card';
import { ListSkeleton } from '../../../components/ui/Skeleton';
import { ErrorMessage } from '../../../components/ui/ErrorMessage';
import { ListPage } from '../../../components/ListPage';
import { useCollectionQuery, type CollectionQuery } from '../../../hooks/useCollectionQuery';
import { ActivityPointsMapView } from './ActivityPointsMapView';

const CATEGORY_OPTIONS = [
  { value: 'all', label: 'Tous' },
  ...Object.entries(CATEGORY_META).map(([value, meta]) => ({ value, label: meta.label })),
];

type View = 'list' | 'map';

function ViewToggle({ view, onChange }: { view: View; onChange: (view: View) => void }) {
  const base = 'px-3 py-1.5 text-sm font-medium rounded-lg transition-colors';
  const active = 'bg-white text-surface-900 shadow-sm';
  const inactive = 'text-surface-500 hover:text-surface-700';
  return (
    <div className="flex items-center gap-1 rounded-xl bg-surface-100 p-1">
      <button
        type="button"
        className={`${base} ${view === 'list' ? active : inactive}`}
        aria-pressed={view === 'list'}
        onClick={() => onChange('list')}
      >
        Liste
      </button>
      <button
        type="button"
        className={`${base} ${view === 'map' ? active : inactive}`}
        aria-pressed={view === 'map'}
        onClick={() => onChange('map')}
      >
        Carte
      </button>
    </div>
  );
}

function MapView() {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const points = useAppSelector(selectActivityPointsMapItems);
  const status = useAppSelector(selectActivityPointsMapStatus);
  const error = useAppSelector(selectActivityPointsMapError);
  const saveState = useAppSelector(selectActivityPointSaveState);
  const saveError = useAppSelector(selectActivityPointSaveError);

  useEffect(() => {
    dispatch(fetchAllActivityPoints());
  }, [dispatch]);

  const handleMove = (point: ActivityPoint, latitude: number, longitude: number) => {
    dispatch(
      updateActivityPoint({
        id: point.id,
        payload: {
          name: point.name,
          description: point.description,
          category: point.category,
          latitude,
          longitude,
          articleUrl: point.articleUrl,
        },
      })
    );
  };

  if (status === 'loading' || status === 'idle') return <ListSkeleton />;
  if (status === 'failed') return <ErrorMessage message={error} />;

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between gap-4">
        <p className="text-sm text-surface-500">
          Glissez un marqueur pour déplacer le point : la nouvelle position est
          enregistrée dès que vous le relâchez.
        </p>
        <span className="shrink-0 text-sm" aria-live="polite">
          {saveState === 'saving' && (
            <span className="text-surface-500">Enregistrement…</span>
          )}
          {saveState === 'saved' && (
            <span className="font-medium text-success-600">Position enregistrée</span>
          )}
          {saveState === 'error' && (
            <span className="font-medium text-danger-600">{saveError}</span>
          )}
        </span>
      </div>
      <ActivityPointsMapView points={points} onMove={handleMove} />
      {points.length === 0 && (
        <p className="text-sm text-surface-400">
          Aucun point sur la carte.{' '}
          <button
            type="button"
            className="font-medium text-primary-600 hover:text-primary-700"
            onClick={() => navigate('/activity-points/new')}
          >
            Créer le premier point
          </button>
        </p>
      )}
    </div>
  );
}

export function ActivityPointsPage() {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const [view, setView] = useState<View>('map');
  const points = useAppSelector(selectActivityPoints);
  const status = useAppSelector(selectActivityPointsStatus);
  const error = useAppSelector(selectActivityPointsError);
  const total = useAppSelector(selectActivityPointsCount);
  const page = useAppSelector(selectActivityPointsPage);
  const itemsPerPage = useAppSelector(selectActivityPointsPerPage);

  const fetchPage = useCallback(
    (query: CollectionQuery) => {
      dispatch(
        fetchActivityPoints({ page: query.page, search: query.search, category: query.filter })
      );
    },
    [dispatch]
  );

  const { search, filter, onSearchChange, onFilterChange, setPage, reload } =
    useCollectionQuery(fetchPage);

  const removePoint = async (point: ActivityPoint) => {
    if (!window.confirm(`Supprimer le point « ${point.name} » de la carte ?`)) return;
    await dispatch(deleteActivityPoint({ id: point.id }));
    reload();
  };

  const headerAction = (
    <div className="flex items-center gap-3">
      <ViewToggle view={view} onChange={setView} />
      <Button onClick={() => navigate('/activity-points/new')}>+ Nouveau point</Button>
    </div>
  );

  if (view === 'map') {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Carte des activités"
          subtitle="Gérez les points affichés sur la carte des activités de Rodrigues."
          action={headerAction}
        />
        <MapView />
      </div>
    );
  }

  return (
    <ListPage
      title="Carte des activités"
      subtitle="Gérez les points affichés sur la carte des activités de Rodrigues."
      count={total}
      search={search}
      onSearchChange={onSearchChange}
      searchPlaceholder="Rechercher par nom ou description…"
      filterOptions={CATEGORY_OPTIONS}
      filterValue={filter}
      onFilterChange={onFilterChange}
      status={status}
      error={error}
      isEmpty={points.length === 0}
      emptyMessage="Aucun point sur la carte. Créez-en un avec le bouton « Nouveau point »."
      page={page}
      itemsPerPage={itemsPerPage}
      onPageChange={setPage}
      headerAction={headerAction}
    >
      <Table>
        <THead>
          <TH>Point</TH>
          <TH>Type</TH>
          <TH>Position</TH>
          <TH>Article</TH>
          <TH />
        </THead>
        <TBody>
          {points.map((point) => {
            const meta = CATEGORY_META[point.category];
            return (
              <TR key={point.id}>
                <TD>
                  <div className="flex items-center gap-3">
                    <span
                      className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-base"
                      style={{ background: meta.color }}
                    >
                      {meta.emoji}
                    </span>
                    <div>
                      <div className="font-medium text-surface-900">{point.name}</div>
                      <div className="max-w-md truncate text-xs text-surface-400">
                        {point.description}
                      </div>
                    </div>
                  </div>
                </TD>
                <TD>
                  <Badge variant="surface">{meta.label}</Badge>
                </TD>
                <TD>
                  <span className="text-xs text-surface-500">
                    {point.latitude.toFixed(4)}, {point.longitude.toFixed(4)}
                  </span>
                </TD>
                <TD>
                  {point.articleUrl ? (
                    <a
                      href={point.articleUrl}
                      target="_blank"
                      rel="noreferrer"
                      className="text-sm font-medium text-primary-600 hover:text-primary-700"
                    >
                      Voir l'article
                    </a>
                  ) : (
                    <span className="text-sm text-surface-400">—</span>
                  )}
                </TD>
                <TD>
                  <div className="flex items-center justify-end gap-2">
                    <Button
                      variant="secondary"
                      size="sm"
                      onClick={() => navigate(`/activity-points/${point.id}/edit`)}
                    >
                      Modifier
                    </Button>
                    <Button variant="danger" size="sm" onClick={() => removePoint(point)}>
                      Supprimer
                    </Button>
                  </div>
                </TD>
              </TR>
            );
          })}
        </TBody>
      </Table>
    </ListPage>
  );
}
