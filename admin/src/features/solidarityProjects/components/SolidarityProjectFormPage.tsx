import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { Card, PageHeader } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Field, Input } from '../../../components/ui/Input';
import { HtmlEditor } from '../../../components/ui/HtmlEditor';
import { ErrorMessage } from '../../../components/ui/ErrorMessage';
import { ListSkeleton } from '../../../components/ui/Skeleton';
import {
  createSolidarityProject,
  currentCleared,
  fetchSolidarityProjectById,
  saveStateReset,
  updateSolidarityProject,
} from '../SolidarityProjectsSlice';
import {
  selectSolidarityProjectCurrent,
  selectSolidarityProjectCurrentError,
  selectSolidarityProjectCurrentStatus,
  selectSolidarityProjectSaveError,
  selectSolidarityProjectSaveState,
} from '../SolidarityProjectsSelectors';
import type { KeyFigure } from '../SolidarityProjectsTypes';

export function SolidarityProjectFormPage() {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const isEdit = Boolean(id);

  const current = useAppSelector(selectSolidarityProjectCurrent);
  const currentStatus = useAppSelector(selectSolidarityProjectCurrentStatus);
  const currentError = useAppSelector(selectSolidarityProjectCurrentError);
  const saveState = useAppSelector(selectSolidarityProjectSaveState);
  const saveError = useAppSelector(selectSolidarityProjectSaveError);

  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [imageUrl, setImageUrl] = useState('');
  const [active, setActive] = useState(true);
  const [keyFigures, setKeyFigures] = useState<KeyFigure[]>([]);

  // Load the project to edit (and clear the slice form state on leave).
  useEffect(() => {
    dispatch(saveStateReset());
    if (id) {
      dispatch(fetchSolidarityProjectById(id));
    }
    return () => {
      dispatch(currentCleared());
    };
  }, [dispatch, id]);

  // Pre-fill the form once the edited project is loaded.
  useEffect(() => {
    if (isEdit && current) {
      setTitle(current.title ?? '');
      setDescription(current.description ?? '');
      setImageUrl(current.imageUrl ?? '');
      setActive(current.status === 'active');
      setKeyFigures(current.keyFigures ?? []);
    }
  }, [isEdit, current]);

  const saving = saveState === 'saving';
  const canSubmit = title.trim() !== '' && description.trim() !== '' && !saving;

  const updateKeyFigure = (index: number, patch: Partial<KeyFigure>) => {
    setKeyFigures((rows) => rows.map((row, i) => (i === index ? { ...row, ...patch } : row)));
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!canSubmit) return;

    const payload = {
      title: title.trim(),
      description: description.trim(),
      imageUrl: imageUrl.trim() === '' ? null : imageUrl.trim(),
      status: active ? ('active' as const) : ('closed' as const),
      keyFigures: keyFigures
        .map((k) => ({ value: k.value.trim(), label: k.label.trim() }))
        .filter((k) => k.value !== '' && k.label !== ''),
    };

    const result =
      isEdit && id
        ? await dispatch(updateSolidarityProject({ id, payload }))
        : await dispatch(createSolidarityProject(payload));

    if (
      updateSolidarityProject.fulfilled.match(result) ||
      createSolidarityProject.fulfilled.match(result)
    ) {
      navigate('/solidarity-projects');
    }
  };

  if (isEdit && (currentStatus === 'loading' || currentStatus === 'idle')) {
    return (
      <div className="space-y-6">
        <PageHeader title="Modifier le projet solidaire" />
        <ListSkeleton rows={4} />
      </div>
    );
  }

  if (isEdit && currentStatus === 'failed') {
    return (
      <div className="space-y-6">
        <PageHeader title="Modifier le projet solidaire" />
        <ErrorMessage message={currentError} />
        <Button variant="secondary" onClick={() => navigate('/solidarity-projects')}>
          Retour à la liste
        </Button>
      </div>
    );
  }

  return (
    <div className="w-full space-y-6">
      <PageHeader
        title={isEdit ? 'Modifier le projet solidaire' : 'Nouveau projet solidaire'}
        subtitle={
          isEdit
            ? 'Mettez à jour les informations de ce projet.'
            : 'Renseignez les informations du nouveau projet.'
        }
      />

      <Card className="p-6">
        <form onSubmit={handleSubmit} className="space-y-5">
          {saveState === 'error' && <ErrorMessage message={saveError} />}

          <div className="grid gap-5 md:grid-cols-2">
            <Field label="Titre">
              <Input
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Reforestation de l'île Rodrigues"
                maxLength={255}
              />
            </Field>

            <Field label="URL de l'image" hint="Optionnel — adresse d'une image illustrant le projet.">
              <Input
                type="url"
                value={imageUrl}
                onChange={(e) => setImageUrl(e.target.value)}
                placeholder="https://…"
              />
            </Field>
          </div>

          <Field
            label="Description (HTML)"
            hint="Collez l'article HTML (généré par Claude Code) ou éditez-le ici. Affiché tel quel sur la page publique."
          >
            <HtmlEditor
              value={description}
              onChange={setDescription}
              placeholder="<h2>Notre objectif</h2> <p>…</p>"
            />
          </Field>

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-sm font-medium text-surface-700">Chiffres clés</span>
              <button
                type="button"
                onClick={() => setKeyFigures((rows) => [...rows, { value: '', label: '' }])}
                className="text-sm font-medium text-primary-600 hover:text-primary-700"
              >
                + Ajouter
              </button>
            </div>
            {keyFigures.length === 0 ? (
              <p className="text-xs text-surface-400">
                Aucun chiffre clé. Ex. « 10 000 » / « arbres plantés ».
              </p>
            ) : (
              <div className="space-y-2">
                {keyFigures.map((figure, index) => (
                  <div key={index} className="flex items-center gap-2">
                    <Input
                      value={figure.value}
                      onChange={(e) => updateKeyFigure(index, { value: e.target.value })}
                      placeholder="Valeur (ex. 10 000)"
                    />
                    <Input
                      value={figure.label}
                      onChange={(e) => updateKeyFigure(index, { label: e.target.value })}
                      placeholder="Libellé (ex. arbres plantés)"
                    />
                    <button
                      type="button"
                      onClick={() => setKeyFigures((rows) => rows.filter((_, i) => i !== index))}
                      className="shrink-0 rounded-lg px-2 py-1 text-sm text-danger-600 hover:bg-danger-50"
                      aria-label="Supprimer ce chiffre clé"
                    >
                      ✕
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <label className="flex items-center gap-2 text-sm text-surface-700">
            <input
              type="checkbox"
              checked={active}
              onChange={(e) => setActive(e.target.checked)}
              className="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500"
            />
            Actif (visible publiquement)
          </label>

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="secondary" onClick={() => navigate('/solidarity-projects')}>
              Annuler
            </Button>
            <Button type="submit" disabled={!canSubmit}>
              {saving ? 'Enregistrement…' : isEdit ? 'Enregistrer les modifications' : 'Créer le projet'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
}
