import React from 'react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { tileA11yHandlers } from './leafletA11y';
import {
  RODRIGUES_BOUNDS_VISCOSITY,
  RODRIGUES_MAX_BOUNDS,
  RODRIGUES_MIN_ZOOM,
} from './rodriguesMapConfig';

delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

interface LocationMapProps {
  latitude: number;
  longitude: number;
  label?: string;
  zoom?: number;
  height?: number;
}

const LocationMap: React.FC<LocationMapProps> = ({ latitude, longitude, label, zoom = 14, height = 320 }) => (
  <div className="relative z-0 rounded-2xl overflow-hidden border border-gray-100" style={{ height }}>
    <MapContainer
      center={[latitude, longitude]}
      zoom={zoom}
      minZoom={RODRIGUES_MIN_ZOOM}
      maxBounds={RODRIGUES_MAX_BOUNDS}
      maxBoundsViscosity={RODRIGUES_BOUNDS_VISCOSITY}
      scrollWheelZoom={false}
      style={{ height: '100%', width: '100%' }}
    >
      <TileLayer
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        eventHandlers={tileA11yHandlers}
      />
      <Marker position={[latitude, longitude]} alt={label ?? ''}>
        {label && <Popup>{label}</Popup>}
      </Marker>
    </MapContainer>
  </div>
);

export default LocationMap;
