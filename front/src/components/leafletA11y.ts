import type { TileEvent } from 'leaflet';

/**
 * Les tuiles OpenStreetMap générées par Leaflet sortent sans attribut alt.
 * Les marquer décoratives (alt="") pour les lecteurs d'écran et les audits
 * SEO. À passer en `eventHandlers` de chaque <TileLayer>.
 */
export const tileA11yHandlers = {
  tileload: (event: TileEvent) => event.tile.setAttribute('alt', ''),
};
