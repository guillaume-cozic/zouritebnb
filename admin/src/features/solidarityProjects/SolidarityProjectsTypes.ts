export interface KeyFigure {
  value: string;
  label: string;
}

export type SolidarityProjectStatus = 'active' | 'closed';

export type SolidarityProjectLocale = 'fr' | 'en';

/** Translatable content of a project for a given locale. */
export interface SolidarityProjectTranslation {
  title: string;
  description: string;
  keyFigures: KeyFigure[];
}

export interface AdminSolidarityProject {
  id: string;
  title: string | null;
  description: string | null;
  imageUrl: string | null;
  status: SolidarityProjectStatus;
  createdAt: string | null;
  isDefault: boolean;
  keyFigures: KeyFigure[];
  /** Per-locale content. `fr` (default) is always present; `en` is optional. */
  translations: Partial<Record<SolidarityProjectLocale, SolidarityProjectTranslation>>;
}

export interface CreateSolidarityProjectPayload {
  title: string;
  description: string;
  imageUrl: string | null;
  status: SolidarityProjectStatus;
  keyFigures: KeyFigure[];
  /**
   * Non-default locales only (never `fr`). Only include a locale when it is
   * fully filled (non-blank title AND description), otherwise omit it.
   */
  translations?: Partial<Record<Exclude<SolidarityProjectLocale, 'fr'>, SolidarityProjectTranslation>>;
}

export type SaveState = 'idle' | 'saving' | 'saved' | 'error';

export type LoadStatus = 'idle' | 'loading' | 'succeeded' | 'failed';

export interface SolidarityProjectsState {
  items: AdminSolidarityProject[];
  page: number;
  itemsPerPage: number;
  totalItems: number;
  status: LoadStatus;
  error: string | null;
  /** The project loaded for the edit page. */
  current: AdminSolidarityProject | null;
  currentStatus: LoadStatus;
  currentError: string | null;
  /** Shared save state for the create/edit form. */
  saveState: SaveState;
  saveError: string | null;
}
