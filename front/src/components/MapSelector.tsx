import React from 'react';
import { MapContainer, TileLayer, Marker, Circle, useMapEvents, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix default marker icon
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

interface MapSelectorProps {
  latitude?: number;
  longitude?: number;
  onSelect: (lat: number, lng: number) => void;
  radiusMeters?: number;
  height?: number;
  defaultCenter?: [number, number];
  defaultZoom?: number;
}

function ClickHandler({ onSelect }: { onSelect: (lat: number, lng: number) => void }) {
  useMapEvents({
    click(e) {
      const lat = Math.round(e.latlng.lat * 10000) / 10000;
      const lng = Math.round(e.latlng.lng * 10000) / 10000;
      onSelect(lat, lng);
    },
  });
  return null;
}

function FlyToMarker({ position }: { position: [number, number] }) {
  const map = useMap();
  React.useEffect(() => {
    map.flyTo(position, Math.max(map.getZoom(), 13), { duration: 0.8 });
  }, [map, position[0], position[1]]); // eslint-disable-line react-hooks/exhaustive-deps
  return null;
}

function MapSelector({
  latitude,
  longitude,
  onSelect,
  radiusMeters = 500,
  height = 350,
  defaultCenter = [46.603354, 1.888334],
  defaultZoom = 5,
}: MapSelectorProps) {
  const hasMarker = latitude !== undefined && longitude !== undefined;
  const markerPosition: [number, number] | null = hasMarker ? [latitude, longitude] : null;

  return (
    <div className="space-y-4">
      <div className="rounded-2xl overflow-hidden border border-gray-200 shadow-sm" style={{ height }}>
        <MapContainer
          center={markerPosition || defaultCenter}
          zoom={markerPosition ? 14 : defaultZoom}
          style={{ height: '100%', width: '100%' }}
          className="z-0"
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <ClickHandler onSelect={onSelect} />
          {markerPosition && (
            <>
              <FlyToMarker position={markerPosition} />
              <Circle
                center={markerPosition}
                radius={radiusMeters}
                pathOptions={{
                  color: '#8b5cf6',
                  fillColor: '#8b5cf6',
                  fillOpacity: 0.1,
                  weight: 2,
                  dashArray: '6 4',
                }}
              />
              <Marker position={markerPosition} />
            </>
          )}
        </MapContainer>
      </div>

      {hasMarker && (
        <div className="flex items-center gap-2 text-xs text-gray-500">
          <svg className="w-3.5 h-3.5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
          </svg>
          <span>{Number(latitude).toFixed(4)}, {Number(longitude).toFixed(4)}</span>
        </div>
      )}
    </div>
  );
}

export default MapSelector;
