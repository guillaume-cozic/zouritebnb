import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { Card, PageHeader } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Field, Input, Select, Textarea } from '../../../components/ui/Input';
import { ErrorMessage } from '../../../components/ui/ErrorMessage';
import { ListSkeleton } from '../../../components/ui/Skeleton';
import {
  createActivityPoint,
  currentCleared,
  fetchActivityPointById,
  saveStateReset,
  updateActivityPoint,
} from '../ActivityPointsSlice';
import {
  selectActivityPointCurrent,
  selectActivityPointCurrentError,
  selectActivityPointCurrentStatus,
  selectActivityPointSaveError,
  selectActivityPointSaveState,
} from '../ActivityPointsSelectors';
import type { ActivityPointCategory, SaveActivityPointPayload } from '../ActivityPointsTypes';
import { ACTIVITY_POINT_CATEGORIES, CATEGORY_META } from '../ActivityPointsTypes';
import { ActivityPointMapPicker } from './ActivityPointMapPicker';

export function ActivityPointFormPage() {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const isEdit = Boolean(id);

  const current = useAppSelector(selectActivityPointCurrent);
  const currentStatus = useAppSelector(selectActivityPointCurrentStatus);
  const currentError = useAppSelector(selectActivityPointCurrentError);
  const saveState = useAppSelector(selectActivityPointSaveState);
  const saveError = useAppSelector(selectActivityPointSaveError);

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState<ActivityPointCategory>('beach');
  const [latitude, setLatitude] = useState<number | null>(null);
  const [longitude, setLongitude] = useState<number | null>(null);
  const [articleUrl, setArticleUrl] = useState('');

  // Load the point to edit (and clear the slice form state on leave).
  useEffect(() => {
    dispatch(saveStateReset());
    if (id) {
      dispatch(fetchActivityPointById(id));
    }
    return () => {
      dispatch(currentCleared());
    };
  }, [dispatch, id]);

  // Pre-fill the form once the edited point is loaded.
  useEffect(() => {
    if (isEdit && current) {
      setName(current.name);
      setDescription(current.description);
      setCategory(current.category);
      setLatitude(current.latitude);
      setLongitude(current.longitude);
      setArticleUrl(current.articleUrl ?? '');
    }
  }, [isEdit, current]);

  const saving = saveState === 'saving';
  const canSubmit =
    name.trim() !== '' &&
    description.trim() !== '' &&
    latitude !== null &&
    longitude !== null &&
    !saving;

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!canSubmit || latitude === null || longitude === null) return;

    const payload: SaveActivityPointPayload = {
      name: name.trim(),
      description: description.trim(),
      category,
      latitude,
      longitude,
      articleUrl: articleUrl.trim() === '' ? null : articleUrl.trim(),
    };

    const result =
      isEdit && id
        ? await dispatch(updateActivityPoint({ id, payload }))
        : await dispatch(createActivityPoint(payload));

    if (
      updateActivityPoint.fulfilled.match(result) ||
      createActivityPoint.fulfilled.match(result)
    ) {
      navigate('/activity-points');
    }
  };

  if (isEdit && (currentStatus === 'loading' || currentStatus === 'idle')) {
    return (
      <div className="space-y-6">
        <PageHeader title="Modifier le point" />
        <ListSkeleton rows={4} />
      </div>
    );
  }

  if (isEdit && currentStatus === 'failed') {
    return (
      <div className="space-y-6">
        <PageHeader title="Modifier le point" />
        <ErrorMessage message={currentError} />
        <Button variant="secondary" onClick={() => navigate('/activity-points')}>
          Retour à la liste
        </Button>
      </div>
    );
  }

  return (
    <div className="w-full space-y-6">
      <PageHeader
        title={isEdit ? 'Modifier le point' : 'Nouveau point sur la carte'}
        subtitle={
          isEdit
            ? 'Mettez à jour les informations de ce point.'
            : 'Cliquez sur la carte pour positionner le point, puis renseignez ses informations.'
        }
      />

      <Card className="p-6">
        <form onSubmit={handleSubmit} className="space-y-5">
          {saveState === 'error' && <ErrorMessage message={saveError} />}

          <div className="grid gap-5 md:grid-cols-2">
            <Field label="Nom">
              <Input
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Anse Mourouk"
                maxLength={255}
              />
            </Field>

            <Field label="Type">
              <Select
                value={category}
                onChange={(e) => setCategory(e.target.value as ActivityPointCategory)}
              >
                {ACTIVITY_POINT_CATEGORIES.map((cat) => (
                  <option key={cat} value={cat}>
                    {CATEGORY_META[cat].emoji} {CATEGORY_META[cat].label}
                  </option>
                ))}
              </Select>
            </Field>
          </div>

          <Field label="Description">
            <Textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Paradis des kitesurfeurs, plage immense avec vent soutenu toute l'année."
            />
          </Field>

          <Field
            label="Lien vers un article"
            hint="Optionnel — URL d'un article du blog (ou externe) affiché dans la popup du point."
          >
            <Input
              type="url"
              value={articleUrl}
              onChange={(e) => setArticleUrl(e.target.value)}
              placeholder="https://…/blog/anse-mourouk"
            />
          </Field>

          <Field
            label="Position sur la carte"
            hint="Cliquez sur la carte pour placer le point, ou déplacez le marqueur."
          >
            <div className="space-y-2">
              <ActivityPointMapPicker
                latitude={latitude}
                longitude={longitude}
                category={category}
                onChange={(lat, lng) => {
                  setLatitude(lat);
                  setLongitude(lng);
                }}
              />
              <p className="text-xs text-surface-400">
                {latitude !== null && longitude !== null
                  ? `Latitude : ${latitude.toFixed(5)} — Longitude : ${longitude.toFixed(5)}`
                  : 'Aucune position sélectionnée.'}
              </p>
            </div>
          </Field>

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="secondary" onClick={() => navigate('/activity-points')}>
              Annuler
            </Button>
            <Button type="submit" disabled={!canSubmit}>
              {saving ? 'Enregistrement…' : isEdit ? 'Enregistrer les modifications' : 'Créer le point'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
}
