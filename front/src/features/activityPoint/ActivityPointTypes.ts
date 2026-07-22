export type ActivityPointCategory =
  | 'kitesurf'
  | 'viewpoint'
  | 'nature'
  | 'beach'
  | 'diving'
  | 'heritage'
  | 'activity';

/** A point of interest shown on the Rodrigues activities map. */
export interface ActivityPoint {
  id: string;
  name: string;
  description: string;
  category: ActivityPointCategory;
  latitude: number;
  longitude: number;
  /** Optional link to a related article, shown in the marker popup. */
  articleUrl: string | null;
}
