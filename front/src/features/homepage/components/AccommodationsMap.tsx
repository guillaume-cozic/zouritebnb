import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { MapContainer, TileLayer, Marker, Popup, useMap, useMapEvents } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { tileA11yHandlers } from '../../../components/leafletA11y';
import { accommodationPath } from '../../accommodation/accommodationUrl';
import { AccommodationListItem, MapBounds } from '../HomepageTypes';
import {
  RODRIGUES_BOUNDS_VISCOSITY,
  RODRIGUES_MAX_BOUNDS,
  RODRIGUES_MIN_ZOOM,
} from '../../../components/rodriguesMapConfig';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

// Center on Rodrigues Island, used only when no accommodation is geolocated.
const DEFAULT_CENTER: [number, number] = [-19.7245, 63.4272];

interface GeoAccommodation extends AccommodationListItem {
  latitude: number;
  longitude: number;
}

const hasCoordinates = (a: AccommodationListItem): a is GeoAccommodation =>
  typeof a.latitude === 'number' && typeof a.longitude === 'number';

const createPriceIcon = (price: number | null, highlighted = false): L.DivIcon => {
  const label = price != null ? `${price} €` : '•';
  return L.divIcon({
    className: 'accommodations-map-marker',
    html: `<div style="
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 9999px;
      border: 1px solid;
      font-size: 13px;
      font-weight: 700;
      white-space: nowrap;
      transition: transform 0.12s ease;
      ${highlighted
        ? 'background:#111827;border-color:#111827;color:#ffffff;transform:scale(1.18);box-shadow:0 4px 12px rgba(0,0,0,0.35);'
        : 'background:#ffffff;border-color:#e5e7eb;color:#111827;box-shadow:0 2px 6px rgba(0,0,0,0.18);'}
    ">${label}</div>`,
    iconSize: [0, 0],
    iconAnchor: [0, 14],
    popupAnchor: [0, -14],
  });
};

interface FitBoundsProps {
  points: [number, number][];
  enabled: boolean;
}

// Adjusts the viewport so every marker is visible whenever the result set changes.
// Disabled while a "search this area" filter is active, so the map stays where the
// user put it instead of snapping back to the results and fighting their gesture.
const FitBounds: React.FC<FitBoundsProps> = ({ points, enabled }) => {
  const map = useMap();

  useEffect(() => {
    if (!enabled || points.length === 0) return;
    if (points.length === 1) {
      map.setView(points[0], 14);
      return;
    }
    map.fitBounds(L.latLngBounds(points), { padding: [48, 48], maxZoom: 15 });
  }, [map, points, enabled]);

  return null;
};

const boundsOf = (map: L.Map): MapBounds => {
  const b = map.getBounds();
  return { north: b.getNorth(), south: b.getSouth(), east: b.getEast(), west: b.getWest() };
};

interface ViewportWatcherProps {
  searchAsIMove: boolean;
  onSearchArea?: (bounds: MapBounds) => void;
  onMoved: (bounds: MapBounds) => void;
}

// Reports viewport changes to the parent (for the "search this area" button) and,
// when auto-search is on, re-runs the search on move after a short debounce.
const MapViewportWatcher: React.FC<ViewportWatcherProps> = ({ searchAsIMove, onSearchArea, onMoved }) => {
  const timer = useRef<number | null>(null);
  const map = useMapEvents({
    moveend: () => {
      const bounds = boundsOf(map);
      onMoved(bounds);
      if (searchAsIMove && onSearchArea) {
        if (timer.current) window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => onSearchArea(bounds), 600);
      }
    },
  });

  useEffect(() => () => {
    if (timer.current) window.clearTimeout(timer.current);
  }, []);

  return null;
};

interface AccommodationsMapProps {
  accommodations: AccommodationListItem[];
  height?: number | string;
  highlightedId?: string | null;
  /** When set, enables the "search this area" controls that re-run the search on the map viewport. */
  onSearchArea?: (bounds: MapBounds) => void;
  searchAsIMove?: boolean;
  onSearchAsIMoveChange?: (value: boolean) => void;
  /** Auto-fit the viewport to the markers when results change. Disable while a zone filter is active. */
  autoFit?: boolean;
}

const AccommodationsMap: React.FC<AccommodationsMapProps> = ({
  accommodations,
  height = 480,
  highlightedId = null,
  onSearchArea,
  searchAsIMove = false,
  onSearchAsIMoveChange,
  autoFit = true,
}) => {
  const { t } = useTranslation();
  const [movedBounds, setMovedBounds] = useState<MapBounds | null>(null);
  const [movedSinceSearch, setMovedSinceSearch] = useState(false);

  const geoAccommodations = useMemo(
    () => accommodations.filter(hasCoordinates),
    [accommodations],
  );

  const points = useMemo<[number, number][]>(
    () => geoAccommodations.map((a) => [a.latitude, a.longitude]),
    [geoAccommodations],
  );

  const interactive = typeof onSearchArea === 'function';

  // Without the interactive search controls, an empty result set has no map to show.
  // With them, the map must stay on screen so the user can pan/zoom and search again.
  if (geoAccommodations.length === 0 && !interactive) {
    return (
      <div
        className="rounded-2xl border border-surface-100 bg-surface-50 flex items-center justify-center text-sm text-surface-500"
        style={{ height }}
      >
        {t('listing.mapEmpty')}
      </div>
    );
  }

  const handleSearchHere = () => {
    const bounds = movedBounds;
    if (bounds && onSearchArea) {
      onSearchArea(bounds);
      setMovedSinceSearch(false);
    }
  };

  return (
    <div
      className="relative z-0 rounded-2xl overflow-hidden border border-surface-100 shadow-sm"
      style={{ height }}
    >
      <MapContainer
        center={points[0] ?? DEFAULT_CENTER}
        zoom={12}
        minZoom={RODRIGUES_MIN_ZOOM}
        maxBounds={RODRIGUES_MAX_BOUNDS}
        maxBoundsViscosity={RODRIGUES_BOUNDS_VISCOSITY}
        scrollWheelZoom={true}
        style={{ height: '100%', width: '100%' }}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          eventHandlers={tileA11yHandlers}
        />
        <FitBounds points={points} enabled={autoFit} />
        {interactive && (
          <MapViewportWatcher
            searchAsIMove={searchAsIMove}
            onSearchArea={onSearchArea}
            onMoved={(b) => {
              setMovedBounds(b);
              setMovedSinceSearch(true);
            }}
          />
        )}
        {geoAccommodations.map((item) => {
          const photo = item.photoUrls?.[0] ?? item.thumbnailUrl;
          const isHighlighted = item.id === highlightedId;
          return (
            <Marker
              key={item.id}
              position={[item.latitude, item.longitude]}
              icon={createPriceIcon(item.price, isHighlighted)}
              zIndexOffset={isHighlighted ? 1000 : 0}
              alt={item.title}
            >
              <Popup>
                <Link
                  to={accommodationPath(item)}
                  className="block no-underline text-inherit"
                  style={{ minWidth: 200 }}
                >
                  {photo && (
                    <img
                      src={`${API_BASE}${photo}`}
                      alt={item.title}
                      className="w-full h-28 object-cover rounded-lg mb-2"
                    />
                  )}
                  <strong className="text-gray-900">{item.title}</strong>
                  <div className="text-xs text-gray-500 mt-0.5">
                    {[item.city, item.country].filter(Boolean).join(', ') || '—'}
                  </div>
                  {item.price != null && (
                    <div className="text-sm font-bold text-gray-900 mt-1">
                      {item.price}{' '}€
                      <span className="font-normal text-gray-500"> / {t('homepage.night')}</span>
                    </div>
                  )}
                </Link>
              </Popup>
            </Marker>
          );
        })}
      </MapContainer>

      {interactive && (
        <>
          {/* "Search this area" — appears once the user has panned/zoomed the map. */}
          {movedSinceSearch && !searchAsIMove && (
            <button
              type="button"
              onClick={handleSearchHere}
              className="absolute z-[1000] top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-2 rounded-full bg-white border border-surface-200 shadow-md px-4 py-2 text-sm font-semibold text-surface-800 hover:bg-surface-50 transition-colors"
            >
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
              </svg>
              {t('listing.searchThisArea')}
            </button>
          )}

          {/* Empty result set inside the current zone — keep the map so the user can move on. */}
          {geoAccommodations.length === 0 && (
            <div className="absolute z-[1000] top-3 left-1/2 -translate-x-1/2 rounded-full bg-white/95 border border-surface-200 shadow-sm px-4 py-2 text-sm text-surface-500">
              {t('listing.mapEmpty')}
            </div>
          )}

          {/* Auto-search toggle: re-run the search whenever the map moves. */}
          {onSearchAsIMoveChange && (
            <label className="absolute z-[1000] bottom-3 left-3 inline-flex items-center gap-2 rounded-lg bg-white/95 border border-surface-200 shadow-sm px-3 py-2 text-xs font-medium text-surface-700 cursor-pointer select-none">
              <input
                type="checkbox"
                checked={searchAsIMove}
                onChange={(e) => onSearchAsIMoveChange(e.target.checked)}
                className="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500/30"
              />
              {t('listing.searchAsIMove')}
            </label>
          )}
        </>
      )}
    </div>
  );
};

export default AccommodationsMap;
