import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { tileA11yHandlers } from '../../../components/leafletA11y';
import {
  RODRIGUES_BOUNDS_VISCOSITY,
  RODRIGUES_MAX_BOUNDS,
  RODRIGUES_MIN_ZOOM,
} from '../../../components/rodriguesMapConfig';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchActivityPoints } from '../../activityPoint/ActivityPointSlice';
import {
  selectActivityPoints,
  selectActivityPointsStatus,
} from '../../activityPoint/ActivityPointSelectors';
import type { ActivityPointCategory } from '../../activityPoint/ActivityPointTypes';

export const CATEGORY_META: Record<ActivityPointCategory, { label: string; color: string; emoji: string }> = {
  kitesurf: { label: 'Kitesurf', color: '#0ea5e9', emoji: '🪁' },
  viewpoint: { label: 'Point de vue', color: '#f97316', emoji: '🗻' },
  nature: { label: 'Parc & nature', color: '#16a34a', emoji: '🌳' },
  beach: { label: 'Plage', color: '#eab308', emoji: '🏖️' },
  diving: { label: 'Plongée', color: '#2563eb', emoji: '🤿' },
  heritage: { label: 'Patrimoine', color: '#a855f7', emoji: '🏛️' },
  activity: { label: 'Activité', color: '#ec4899', emoji: '🎯' },
};

const createIcon = (category: ActivityPointCategory): L.DivIcon => {
  const { color, emoji } = CATEGORY_META[category];
  return L.divIcon({
    className: 'rodrigues-map-marker',
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
    popupAnchor: [0, -18],
  });
};

const RODRIGUES_CENTER: [number, number] = [-19.7245, 63.4272];

interface RodriguesMapProps {
  /** 'h2' on the homepage section (default); 'h1' on the dedicated /activites page. */
  headingLevel?: 'h1' | 'h2';
}

const RodriguesMap: React.FC<RodriguesMapProps> = ({ headingLevel = 'h2' }) => {
  const dispatch = useAppDispatch();
  const points = useAppSelector(selectActivityPoints);
  const status = useAppSelector(selectActivityPointsStatus);

  const [activeCategories, setActiveCategories] = useState<Set<ActivityPointCategory>>(
    () => new Set(Object.keys(CATEGORY_META) as ActivityPointCategory[]),
  );

  useEffect(() => {
    dispatch(fetchActivityPoints());
  }, [dispatch]);

  const toggle = (category: ActivityPointCategory) => {
    setActiveCategories((prev) => {
      const next = new Set(prev);
      if (next.has(category)) next.delete(category);
      else next.add(category);
      return next;
    });
  };

  const filteredPoints = useMemo(
    () =>
      points.filter(
        (point) => point.category in CATEGORY_META && activeCategories.has(point.category),
      ),
    [points, activeCategories],
  );

  const icons = useMemo(() => {
    const map = {} as Record<ActivityPointCategory, L.DivIcon>;
    (Object.keys(CATEGORY_META) as ActivityPointCategory[]).forEach((cat) => {
      map[cat] = createIcon(cat);
    });
    return map;
  }, []);

  if (status === 'succeeded' && points.length === 0) {
    return null;
  }

  return (
    <section className="py-16 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8">
          {headingLevel === 'h1' ? (
            <h1 className="text-3xl font-bold text-gray-900">Carte des activités à Rodrigues</h1>
          ) : (
            <h2 className="text-3xl font-bold text-gray-900">Carte des activités à Rodrigues</h2>
          )}
          <p className="text-gray-500 mt-2">Explorez l'île et ses spots incontournables</p>
        </div>

        <div className="flex flex-wrap justify-center gap-2 mb-6">
          {(Object.keys(CATEGORY_META) as ActivityPointCategory[]).map((cat) => {
            const meta = CATEGORY_META[cat];
            const active = activeCategories.has(cat);
            return (
              <button
                key={cat}
                onClick={() => toggle(cat)}
                className={`inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border transition-all ${
                  active
                    ? 'bg-white shadow-sm border-gray-200 text-gray-900'
                    : 'bg-gray-50 border-gray-100 text-gray-400'
                }`}
                style={active ? { borderColor: meta.color } : undefined}
                type="button"
              >
                <span
                  className="w-3 h-3 rounded-full"
                  style={{ background: active ? meta.color : '#d1d5db' }}
                />
                <span>{meta.emoji}</span>
                {meta.label}
              </button>
            );
          })}
        </div>

        <div
          className="relative z-0 rounded-2xl overflow-hidden border border-gray-100 shadow-lg"
          style={{ height: 520 }}
        >
          <MapContainer
            center={RODRIGUES_CENTER}
            zoom={12}
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
            {filteredPoints.map((point) => (
              <Marker
                key={point.id}
                position={[point.latitude, point.longitude]}
                icon={icons[point.category]}
                alt={point.name}
              >
                <Popup>
                  <div className="space-y-1" style={{ minWidth: 200 }}>
                    <div className="flex items-center gap-2">
                      <span>{CATEGORY_META[point.category].emoji}</span>
                      <strong>{point.name}</strong>
                    </div>
                    <div
                      className="text-xs font-semibold uppercase tracking-wide"
                      style={{ color: CATEGORY_META[point.category].color }}
                    >
                      {CATEGORY_META[point.category].label}
                    </div>
                    <p className="text-sm text-gray-600 m-0">{point.description}</p>
                    {point.articleUrl && (
                      <a
                        href={point.articleUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-block text-sm font-medium text-primary-600 hover:underline"
                      >
                        Lire l'article →
                      </a>
                    )}
                  </div>
                </Popup>
              </Marker>
            ))}
          </MapContainer>
        </div>

        {headingLevel === 'h2' && (
          <div className="text-center mt-6">
            <Link
              to="/activites"
              className="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700"
            >
              Voir toutes les activités →
            </Link>
          </div>
        )}
      </div>
    </section>
  );
};

export default RodriguesMap;
