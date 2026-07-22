export type ActivityPointCategory =
  | 'kitesurf'
  | 'viewpoint'
  | 'nature'
  | 'beach'
  | 'diving'
  | 'heritage'
  | 'activity';

export interface ActivityPoint {
  id: string;
  name: string;
  description: string;
  category: ActivityPointCategory;
  latitude: number;
  longitude: number;
  articleUrl: string | null;
}

export interface SaveActivityPointPayload {
  name: string;
  description: string;
  category: ActivityPointCategory;
  latitude: number;
  longitude: number;
  articleUrl: string | null;
}

export type SaveState = 'idle' | 'saving' | 'saved' | 'error';

export type LoadStatus = 'idle' | 'loading' | 'succeeded' | 'failed';

export interface ActivityPointsState {
  items: ActivityPoint[];
  page: number;
  itemsPerPage: number;
  totalItems: number;
  status: LoadStatus;
  error: string | null;
  /** Every point, unpaginated, for the map view. */
  mapItems: ActivityPoint[];
  mapStatus: LoadStatus;
  mapError: string | null;
  /** The point loaded for the edit page. */
  current: ActivityPoint | null;
  currentStatus: LoadStatus;
  currentError: string | null;
  /** Shared save state for the create/edit form. */
  saveState: SaveState;
  saveError: string | null;
}

export const CATEGORY_META: Record<
  ActivityPointCategory,
  { label: string; color: string; emoji: string }
> = {
  kitesurf: { label: 'Kitesurf', color: '#0ea5e9', emoji: '🪁' },
  viewpoint: { label: 'Point de vue', color: '#f97316', emoji: '🗻' },
  nature: { label: 'Parc & nature', color: '#16a34a', emoji: '🌳' },
  beach: { label: 'Plage', color: '#eab308', emoji: '🏖️' },
  diving: { label: 'Plongée', color: '#2563eb', emoji: '🤿' },
  heritage: { label: 'Patrimoine', color: '#a855f7', emoji: '🏛️' },
  activity: { label: 'Activité', color: '#ec4899', emoji: '🎯' },
};

export const ACTIVITY_POINT_CATEGORIES = Object.keys(
  CATEGORY_META,
) as ActivityPointCategory[];
