import React, { useMemo, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

type Category = 'kitesurf' | 'viewpoint' | 'nature' | 'beach' | 'diving' | 'heritage';

interface Activity {
  id: string;
  name: string;
  description: string;
  category: Category;
  latitude: number;
  longitude: number;
}

const ACTIVITIES: Activity[] = [
  {
    id: 'pointe-coton',
    name: 'Pointe Coton',
    description: "Spot emblématique de kitesurf, avec vent régulier et lagon turquoise.",
    category: 'kitesurf',
    latitude: -19.6964,
    longitude: 63.4793,
  },
  {
    id: 'anse-mourouk',
    name: 'Anse Mourouk',
    description: "Paradis des kitesurfeurs, plage immense avec vent soutenu toute l'année.",
    category: 'kitesurf',
    latitude: -19.7583,
    longitude: 63.4317,
  },
  {
    id: 'mont-limon',
    name: 'Mont Limon',
    description: "Point culminant de l'île (398 m), panorama à 360° sur le lagon.",
    category: 'viewpoint',
    latitude: -19.7161,
    longitude: 63.4236,
  },
  {
    id: 'mont-lubin',
    name: 'Mont Lubin',
    description: "Vue imprenable sur les collines et la côte nord.",
    category: 'viewpoint',
    latitude: -19.7128,
    longitude: 63.4131,
  },
  {
    id: 'grande-montagne',
    name: 'Réserve Grande Montagne',
    description: "Réserve naturelle avec espèces endémiques et sentiers balisés.",
    category: 'nature',
    latitude: -19.7036,
    longitude: 63.4450,
  },
  {
    id: 'francois-leguat',
    name: 'Réserve François Leguat',
    description: "Parc aux tortues géantes et grotte Caverne Patate.",
    category: 'nature',
    latitude: -19.7672,
    longitude: 63.4181,
  },
  {
    id: 'anse-aux-anglais',
    name: 'Anse aux Anglais (English Bay)',
    description: "Plage facilement accessible, restaurants et maisons d'hôtes à proximité.",
    category: 'beach',
    latitude: -19.6736,
    longitude: 63.4283,
  },
  {
    id: 'riviere-banane',
    name: 'Rivière Banane',
    description: "Retraite paisible idéale pour le snorkeling dans l'Aquarium Naturel.",
    category: 'beach',
    latitude: -19.6750,
    longitude: 63.4619,
  },
  {
    id: 'torrent',
    name: 'Torrent',
    description: "Spot pittoresque et isolé au littoral rocheux, excellent pour le snorkeling.",
    category: 'beach',
    latitude: -19.7094,
    longitude: 63.4833,
  },
  {
    id: 'anse-ali',
    name: 'Anse Ali',
    description: "Longue plage paisible avec parking, adaptée à toute la famille.",
    category: 'beach',
    latitude: -19.7303,
    longitude: 63.4875,
  },
  {
    id: 'anse-bouteille',
    name: 'Anse Bouteille',
    description: "Accessible uniquement à pied, plage tranquille et préservée parfaite pour le snorkeling.",
    category: 'beach',
    latitude: -19.7361,
    longitude: 63.4856,
  },
  {
    id: 'anse-femi',
    name: 'Anse Femi',
    description: "Plage privée aux eaux turquoise, accès par piste et sentier de randonnée.",
    category: 'beach',
    latitude: -19.7500,
    longitude: 63.4836,
  },
  {
    id: 'graviers',
    name: 'Graviers',
    description: "Longue plage idéale pour pique-nique, balades côtières et baignade — départ vers Trou d'Argent.",
    category: 'beach',
    latitude: -19.7556,
    longitude: 63.4742,
  },
  {
    id: 'ile-aux-cocos',
    name: 'Île aux Cocos',
    description: "Réserve ornithologique protégée, excursion en bateau incontournable.",
    category: 'nature',
    latitude: -19.7533,
    longitude: 63.2789,
  },
  {
    id: 'caverne-patate',
    name: 'Caverne Patate',
    description: "Grotte calcaire spectaculaire avec stalactites et stalagmites.",
    category: 'heritage',
    latitude: -19.7561,
    longitude: 63.3997,
  },
  {
    id: 'passe-graviers',
    name: 'Passe aux Graviers',
    description: "Site de plongée réputé, récifs coralliens et faune marine.",
    category: 'diving',
    latitude: -19.6708,
    longitude: 63.4600,
  },
  {
    id: 'port-mathurin',
    name: 'Port Mathurin',
    description: "Capitale de l'île, marché coloré le samedi matin.",
    category: 'heritage',
    latitude: -19.6839,
    longitude: 63.4178,
  },
];

const CATEGORY_META: Record<Category, { label: string; color: string; emoji: string }> = {
  kitesurf: { label: 'Kitesurf', color: '#0ea5e9', emoji: '🪁' },
  viewpoint: { label: 'Point de vue', color: '#f97316', emoji: '🗻' },
  nature: { label: 'Parc & nature', color: '#16a34a', emoji: '🌳' },
  beach: { label: 'Plage', color: '#eab308', emoji: '🏖️' },
  diving: { label: 'Plongée', color: '#2563eb', emoji: '🤿' },
  heritage: { label: 'Patrimoine', color: '#a855f7', emoji: '🏛️' },
};

const createIcon = (category: Category): L.DivIcon => {
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

const RodriguesMap: React.FC = () => {
  const [activeCategories, setActiveCategories] = useState<Set<Category>>(
    () => new Set(Object.keys(CATEGORY_META) as Category[]),
  );

  const toggle = (category: Category) => {
    setActiveCategories((prev) => {
      const next = new Set(prev);
      if (next.has(category)) next.delete(category);
      else next.add(category);
      return next;
    });
  };

  const filteredActivities = useMemo(
    () => ACTIVITIES.filter((a) => activeCategories.has(a.category)),
    [activeCategories],
  );

  const icons = useMemo(() => {
    const map = {} as Record<Category, L.DivIcon>;
    (Object.keys(CATEGORY_META) as Category[]).forEach((cat) => {
      map[cat] = createIcon(cat);
    });
    return map;
  }, []);

  return (
    <section className="py-16 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8">
          <h2 className="text-3xl font-bold text-gray-900">Carte des activités à Rodrigues</h2>
          <p className="text-gray-500 mt-2">Explorez l'île et ses spots incontournables</p>
        </div>

        <div className="flex flex-wrap justify-center gap-2 mb-6">
          {(Object.keys(CATEGORY_META) as Category[]).map((cat) => {
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
            scrollWheelZoom={false}
            style={{ height: '100%', width: '100%' }}
          >
            <TileLayer
              attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
              url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            {filteredActivities.map((activity) => (
              <Marker
                key={activity.id}
                position={[activity.latitude, activity.longitude]}
                icon={icons[activity.category]}
              >
                <Popup>
                  <div className="space-y-1" style={{ minWidth: 200 }}>
                    <div className="flex items-center gap-2">
                      <span>{CATEGORY_META[activity.category].emoji}</span>
                      <strong>{activity.name}</strong>
                    </div>
                    <div
                      className="text-xs font-semibold uppercase tracking-wide"
                      style={{ color: CATEGORY_META[activity.category].color }}
                    >
                      {CATEGORY_META[activity.category].label}
                    </div>
                    <p className="text-sm text-gray-600 m-0">{activity.description}</p>
                  </div>
                </Popup>
              </Marker>
            ))}
          </MapContainer>
        </div>
      </div>
    </section>
  );
};

export default RodriguesMap;
