import type { LatLngBoundsExpression } from 'leaflet';

/**
 * Shared constraints for every Leaflet map of the app: the platform only covers
 * Rodrigues, so panning is clamped to the island (lagoon included) and zooming
 * out stops while the island still fills the viewport.
 */
export const RODRIGUES_CENTER: [number, number] = [-19.7245, 63.4272];

export const RODRIGUES_MAX_BOUNDS: LatLngBoundsExpression = [
  [-20.05, 62.95],
  [-19.35, 63.95],
];

export const RODRIGUES_MIN_ZOOM = 10;

/** Hard edge: dragging past the bounds is blocked instead of rubber-banding. */
export const RODRIGUES_BOUNDS_VISCOSITY = 1.0;
