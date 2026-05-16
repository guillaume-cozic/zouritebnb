# Skill: React Redux Architecture

## Quand utiliser

Toute création ou modification de composant, slice, ou logique métier dans l'app front.

## Principes fondamentaux

### 1. Les composants sont purement déclaratifs

Les composants React ne font **jamais** :
- d'appels HTTP (`fetch`, `axios`, etc.)
- d'accès direct à `localStorage`, `sessionStorage`
- de manipulation du DOM impérative
- de logique métier complexe

Les composants font **uniquement** :
- **Lire** le state via des selectors (`useAppSelector`)
- **Dispatcher** des actions (`useAppDispatch`)
- **Rendre** du JSX

```tsx
// BIEN — le composant lit et dispatche
function AccommodationList() {
  const accommodations = useAppSelector(selectAccommodations);
  const status = useAppSelector(selectAccommodationsStatus);
  const dispatch = useAppDispatch();

  useEffect(() => {
    dispatch(fetchAccommodations());
  }, [dispatch]);

  if (status === 'loading') return <Spinner />;
  if (status === 'failed') return <Error />;

  return (
    <ul>
      {accommodations.map((a) => (
        <AccommodationCard key={a.id} accommodation={a} />
      ))}
    </ul>
  );
}

// MAL — le composant fait un appel HTTP
function AccommodationList() {
  const [data, setData] = useState([]);
  useEffect(() => {
    fetch('/api/accommodations').then(r => r.json()).then(setData);
  }, []);
  // ...
}
```

### 2. Architecture par feature (vertical slice)

```
src/
├── app/
│   ├── store.ts              # configureStore
│   └── hooks.ts              # useAppDispatch, useAppSelector typés
├── features/
│   └── <feature>/
│       ├── <Feature>Slice.ts     # createSlice + createAsyncThunk
│       ├── <Feature>Selectors.ts # selectors (createSelector si dérivé)
│       ├── <Feature>Types.ts     # interfaces/types du domaine
│       └── components/
│           ├── <Component>.tsx
│           └── ...
├── components/               # composants partagés (Button, Spinner, Layout...)
└── services/
    └── api.ts                # instance unique pour les appels HTTP
```

### 3. Redux Toolkit uniquement

- **`createSlice`** pour les reducers et actions synchrones
- **`createAsyncThunk`** pour les side effects (appels API)
- **`createSelector`** (reselect) pour les selectors dérivés
- **Jamais** de `switch/case` manuels, de reducers purs écrits à la main, ni de middleware custom pour les appels HTTP

### 4. Side effects dans les thunks, jamais dans les composants

Tout appel HTTP passe par un `createAsyncThunk` dans le slice :

```ts
// features/accommodation/AccommodationSlice.ts
export const fetchAccommodations = createAsyncThunk(
  'accommodation/fetchAll',
  async (_, { rejectWithValue }) => {
    const response = await api.get('/api/accommodations');
    return response.data['hydra:member'];
  }
);

const accommodationSlice = createSlice({
  name: 'accommodation',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchAccommodations.pending, (state) => {
        state.status = 'loading';
      })
      .addCase(fetchAccommodations.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload;
      })
      .addCase(fetchAccommodations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      });
  },
});
```

### 5. State shape par feature

Chaque slice suit cette structure de state :

```ts
interface FeatureState {
  items: Item[];                          // liste des entités
  current: Item | null;                   // entité sélectionnée
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}
```

### 6. Selectors dans un fichier dédié

```ts
// features/accommodation/AccommodationSelectors.ts
import { RootState } from '../../app/store';
import { createSelector } from '@reduxjs/toolkit';

export const selectAccommodations = (state: RootState) => state.accommodation.items;
export const selectAccommodationStatus = (state: RootState) => state.accommodation.status;
export const selectAccommodationById = (state: RootState, id: string) =>
  state.accommodation.items.find((a) => a.id === id);

// Selector dérivé avec mémoïsation
export const selectPublishedAccommodations = createSelector(
  selectAccommodations,
  (items) => items.filter((a) => a.status === 'published')
);
```

### 7. Service API centralisé

Un seul fichier `services/api.ts` configure l'instance HTTP. Les thunks l'importent :

```ts
// services/api.ts
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8080',
  headers: { 'Accept': 'application/ld+json' },
});

export default api;
```

### 8. Types dans un fichier dédié par feature

```ts
// features/accommodation/AccommodationTypes.ts
export interface Accommodation {
  '@id'?: string;
  id: string;
  title: string;
  description: string;
  price: number;
  status: 'draft' | 'published'; 
  street?: string;
  city?: string;
  zipCode?: string;
  country?: string;
  latitude?: number;
  longitude?: number;
}
```

## Règles strictes

1. **Zéro `fetch`/`axios` dans un composant** — toujours un thunk
2. **Zéro `useState` pour du state partagé** — le store Redux est la source de vérité. `useState` uniquement pour du state local UI (toggle, input contrôlé avant submit)
3. **Zéro logique métier dans les composants** — extraire dans le slice ou un selector
4. **Pas de `useEffect` pour synchroniser du state** — c'est un signe que le state est mal modélisé
5. **Un composant = une responsabilité** — découper si un composant dispatche plus de 2-3 actions différentes