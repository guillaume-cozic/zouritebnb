import { useMemo } from 'react';
import { Link } from 'react-router-dom';
import { MapContainer, Marker, Popup, TileLayer, Tooltip } from 'react-leaflet';
import type L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import type { ActivityPoint } from '../ActivityPointsTypes';
import { CATEGORY_META } from '../ActivityPointsTypes';
import {
  createIcon,
  RODRIGUES_CENTER,
  RODRIGUES_MAX_BOUNDS,
  RODRIGUES_MIN_ZOOM,
} from './ActivityPointMapPicker';

interface ActivityPointsMapViewProps {
  points: ActivityPoint[];
  onMove: (point: ActivityPoint, latitude: number, longitude: number) => void;
}

interface DraggablePointProps {
  point: ActivityPoint;
  onMove: ActivityPointsMapViewProps['onMove'];
}

function DraggablePoint({ point, onMove }: DraggablePointProps) {
  const icon = useMemo(() => createIcon(point.category), [point.category]);

  return (
    // Keyed on the stored position by the parent: if a save fails, the store
    // keeps the old coordinates and the marker snaps back where it belongs.
    <Marker
      position={[point.latitude, point.longitude]}
      icon={icon}
      draggable
      eventHandlers={{
        dragend(event) {
          const position = (event.target as L.Marker).getLatLng();
          onMove(point, position.lat, position.lng);
        },
      }}
    >
      <Tooltip direction="top" offset={[0, -18]}>
        {point.name}
      </Tooltip>
      <Popup>
        <div className="space-y-1">
          <div className="font-semibold">{point.name}</div>
          <div className="text-xs text-surface-500">
            {CATEGORY_META[point.category].label} —{' '}
            {point.latitude.toFixed(4)}, {point.longitude.toFixed(4)}
          </div>
          <Link
            to={`/activity-points/${point.id}/edit`}
            className="text-sm font-medium text-primary-600 hover:text-primary-700"
          >
            Modifier la fiche
          </Link>
        </div>
      </Popup>
    </Marker>
  );
}

/**
 * Map of Rodrigues showing every activity point. Markers are draggable: drop
 * one to save its new position.
 */
export function ActivityPointsMapView({ points, onMove }: ActivityPointsMapViewProps) {
  return (
    <div
      className="relative z-0 overflow-hidden rounded-xl border border-surface-200"
      style={{ height: 'calc(100vh - 260px)', minHeight: 420 }}
    >
      <MapContainer
        center={RODRIGUES_CENTER}
        zoom={11}
        minZoom={RODRIGUES_MIN_ZOOM}
        maxBounds={RODRIGUES_MAX_BOUNDS}
        maxBoundsViscosity={1.0}
        style={{ height: '100%', width: '100%' }}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        {points.map((point) => (
          <DraggablePoint
            key={`${point.id}-${point.latitude}-${point.longitude}`}
            point={point}
            onMove={onMove}
          />
        ))}
      </MapContainer>
    </div>
  );
}
