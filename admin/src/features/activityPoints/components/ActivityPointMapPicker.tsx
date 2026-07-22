import { useMemo } from 'react';
import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import type { ActivityPointCategory } from '../ActivityPointsTypes';
import { CATEGORY_META } from '../ActivityPointsTypes';

/** Same constraints as the public map: panning stays clamped to Rodrigues. */
export const RODRIGUES_CENTER: [number, number] = [-19.7245, 63.4272];
export const RODRIGUES_MAX_BOUNDS: L.LatLngBoundsExpression = [
  [-20.05, 62.95],
  [-19.35, 63.95],
];
export const RODRIGUES_MIN_ZOOM = 10;

export const createIcon = (category: ActivityPointCategory): L.DivIcon => {
  const { color, emoji } = CATEGORY_META[category];
  return L.divIcon({
    className: 'activity-point-marker',
    html: `<div style="
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: ${color};
      border: 3px solid white;
      box-shadow: 0 2px 6px rgba(0,0,0,0.25);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    ">${emoji}</div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 18],
  });
};

interface ActivityPointMapPickerProps {
  latitude: number | null;
  longitude: number | null;
  category: ActivityPointCategory;
  onChange: (latitude: number, longitude: number) => void;
}

function ClickHandler({ onChange }: { onChange: ActivityPointMapPickerProps['onChange'] }) {
  useMapEvents({
    click(event) {
      onChange(event.latlng.lat, event.latlng.lng);
    },
  });
  return null;
}

/**
 * Leaflet map of Rodrigues on which the admin clicks (or drags the marker)
 * to set the point coordinates.
 */
export function ActivityPointMapPicker({
  latitude,
  longitude,
  category,
  onChange,
}: ActivityPointMapPickerProps) {
  const icon = useMemo(() => createIcon(category), [category]);
  const hasPosition = latitude !== null && longitude !== null;

  return (
    <div className="relative z-0 overflow-hidden rounded-xl border border-surface-200" style={{ height: 380 }}>
      <MapContainer
        center={hasPosition ? [latitude, longitude] : RODRIGUES_CENTER}
        zoom={12}
        minZoom={RODRIGUES_MIN_ZOOM}
        maxBounds={RODRIGUES_MAX_BOUNDS}
        maxBoundsViscosity={1.0}
        style={{ height: '100%', width: '100%' }}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        <ClickHandler onChange={onChange} />
        {hasPosition && (
          <Marker
            position={[latitude, longitude]}
            icon={icon}
            draggable
            eventHandlers={{
              dragend(event) {
                const position = (event.target as L.Marker).getLatLng();
                onChange(position.lat, position.lng);
              },
            }}
          />
        )}
      </MapContainer>
    </div>
  );
}
