import React, { useEffect, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { AccommodationListItem } from '../HomepageTypes';
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
}

// Adjusts the viewport so every marker is visible whenever the result set changes.
const FitBounds: React.FC<FitBoundsProps> = ({ points }) => {
  const map = useMap();

  useEffect(() => {
    if (points.length === 0) return;
    if (points.length === 1) {
      map.setView(points[0], 14);
      return;
    }
    map.fitBounds(L.latLngBounds(points), { padding: [48, 48], maxZoom: 15 });
  }, [map, points]);

  return null;
};

interface AccommodationsMapProps {
  accommodations: AccommodationListItem[];
  height?: number | string;
  highlightedId?: string | null;
}

const AccommodationsMap: React.FC<AccommodationsMapProps> = ({
  accommodations,
  height = 480,
  highlightedId = null,
}) => {
  const { t } = useTranslation();

  const geoAccommodations = useMemo(
    () => accommodations.filter(hasCoordinates),
    [accommodations],
  );

  const points = useMemo<[number, number][]>(
    () => geoAccommodations.map((a) => [a.latitude, a.longitude]),
    [geoAccommodations],
  );

  if (geoAccommodations.length === 0) {
    return (
      <div
        className="rounded-2xl border border-gray-100 bg-gray-50 flex items-center justify-center text-sm text-gray-500"
        style={{ height }}
      >
        {t('listing.mapEmpty')}
      </div>
    );
  }

  return (
    <div
      className="relative z-0 rounded-2xl overflow-hidden border border-gray-100 shadow-sm"
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
        />
        <FitBounds points={points} />
        {geoAccommodations.map((item) => {
          const photo = item.photoUrls?.[0] ?? item.thumbnailUrl;
          const isHighlighted = item.id === highlightedId;
          return (
            <Marker
              key={item.id}
              position={[item.latitude, item.longitude]}
              icon={createPriceIcon(item.price, isHighlighted)}
              zIndexOffset={isHighlighted ? 1000 : 0}
            >
              <Popup>
                <Link
                  to={`/accommodations/${item.id}`}
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
    </div>
  );
};

export default AccommodationsMap;
