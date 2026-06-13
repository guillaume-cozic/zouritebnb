export interface KeyFigure {
  value: string;
  label: string;
}

export type SolidarityProjectStatus = 'active' | 'closed';

export interface AdminSolidarityProject {
  id: string;
  title: string | null;
  description: string | null;
  imageUrl: string | null;
  status: SolidarityProjectStatus;
  createdAt: string | null;
  isDefault: boolean;
  keyFigures: KeyFigure[];
}

export interface CreateSolidarityProjectPayload {
  title: string;
  description: string;
  imageUrl: string | null;
  status: SolidarityProjectStatus;
  keyFigures: KeyFigure[];
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
