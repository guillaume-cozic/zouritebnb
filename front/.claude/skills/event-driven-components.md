# Skill: Event-Driven Components

## Quand utiliser

À chaque fois qu'un composant doit déclencher plus d'une chose en réaction à une interaction utilisateur (submit, click, mount, change). Avant d'écrire un deuxième `dispatch(...)` à la suite, lire ce skill.

## Principe

Un composant **décrit ce qui vient de se passer**, il **n'orchestre pas** ce qui doit se passer ensuite.

- Le composant dispatch **un seul événement** qui exprime l'intention métier (`reservationConfirmed`, `addressSubmitted`, `teamSettingsOpened`).
- Les slices, thunks ou middlewares **écoutent** cet événement et réagissent. Plusieurs réactions à un même événement sont possibles et même encouragées.
- Un composant ne dispatch **jamais** plusieurs actions à la suite pour coordonner du travail — c'est de la logique métier déguisée en JSX.

> Règle de pouce : si tu écris deux `dispatch(...)` consécutifs dans un composant, tu es en train de mettre la logique au mauvais endroit.

## Comment écouter un événement depuis un autre slice

### `extraReducers` (réaction synchrone sur le state)

Un slice peut réagir à n'importe quelle action (thunk d'un autre slice, action synchrone, action de liste connue) :

```ts
// features/accommodation/AccommodationSlice.ts
import { setLocation } from './AccommodationSlice';
import { addressSubmitted } from './AddressWizardSlice';

const slice = createSlice({
  name: 'accommodationDraft',
  initialState,
  reducers: { /* ... */ },
  extraReducers: (builder) => {
    builder.addCase(addressSubmitted, (state, action) => {
      state.draft.address = action.payload;
    });
  },
});
```

### `createListenerMiddleware` (side effect : dispatcher un thunk en réaction)

Pour enchaîner un appel API ou un autre thunk après un événement, utiliser le listener middleware — pas un deuxième `dispatch` dans le composant :

```ts
// app/listeners.ts
import { createListenerMiddleware } from '@reduxjs/toolkit';
import { addressSubmitted } from '../features/accommodation/AddressWizardSlice';
import { setLocation } from '../features/accommodation/AccommodationSlice';

export const listener = createListenerMiddleware();

listener.startListening({
  actionCreator: addressSubmitted,
  effect: async (action, api) => {
    const { accommodationId, ...address } = action.payload;
    await api.dispatch(setLocation({ id: accommodationId, ...address }));
  },
});
```

Brancher `listener.middleware` dans `configureStore`.

### Plusieurs écouteurs pour un même événement

C'est exactement le but : `addressSubmitted` peut être écouté par le slice draft (mise à jour locale), par le listener middleware (PATCH API), par un slice analytics, etc. Aucun n'est au courant des autres.

## Exemples

### MAL — le composant orchestre

```tsx
// AddressStep.tsx
const onSubmit = (data: FormData) => {
  if (!accommodation?.id) return;
  dispatch(saveDraft({ address: data }));
  dispatch(setLocation({
    id: accommodation.id,
    street: data.street,
    city: data.city,
    /* ... */
  }));
};
```

Le composant connaît trop de choses : il sait qu'il faut sauver le draft **et** appeler l'API. Si demain on ajoute du tracking ou une validation, on revient modifier le composant.

### BIEN — le composant déclare l'intention

```tsx
// AddressStep.tsx
const onSubmit = (data: FormData) => {
  if (!accommodation?.id) return;
  dispatch(addressSubmitted({ accommodationId: accommodation.id, ...data }));
};
```

```ts
// AddressWizardSlice.ts
const slice = createSlice({
  name: 'addressWizard',
  initialState,
  reducers: {
    addressSubmitted: (state, action) => {
      state.draft = action.payload;
    },
  },
});

// listeners.ts
listener.startListening({
  actionCreator: addressSubmitted,
  effect: (action, api) => api.dispatch(setLocation(action.payload)),
});
```

### MAL — coordination dans `useEffect`

```tsx
useEffect(() => {
  if (teamId) {
    dispatch(fetchTeam(teamId));
    dispatch(fetchTeamInvitations(teamId));
  }
  dispatch(fetchSolidarityProjects());
}, [dispatch, teamId]);
```

### BIEN — un seul événement « page ouverte »

```tsx
useEffect(() => {
  dispatch(teamSettingsPageOpened({ teamId }));
}, [dispatch, teamId]);
```

```ts
// listeners.ts
listener.startListening({
  actionCreator: teamSettingsPageOpened,
  effect: async (action, api) => {
    const { teamId } = action.payload;
    if (teamId) {
      api.dispatch(fetchTeam(teamId));
      api.dispatch(fetchTeamInvitations(teamId));
    }
    api.dispatch(fetchSolidarityProjects());
  },
});
```

L'orchestration vit dans le listener, pas dans le composant.

### MAL — clear + fetch dans le composant

```tsx
useEffect(() => {
  if (isOpen) {
    dispatch(clearMutationError());
    if (!accommodationId && accommodations.length === 0) {
      dispatch(fetchAllAccommodations('all'));
    }
  }
}, [isOpen, ...]);
```

### BIEN — un événement d'ouverture, le slice s'occupe du reste

```tsx
useEffect(() => {
  if (isOpen) dispatch(reservationModalOpened({ accommodationId }));
}, [isOpen, accommodationId, dispatch]);
```

```ts
// ReservationSlice.ts → extraReducers
builder.addCase(reservationModalOpened, (state) => {
  state.mutationError = null;
});

// listeners.ts → si une liste est nécessaire
listener.startListening({
  actionCreator: reservationModalOpened,
  effect: (action, api) => {
    const { accommodations } = api.getState().accommodation;
    if (!action.payload.accommodationId && accommodations.length === 0) {
      api.dispatch(fetchAllAccommodations('all'));
    }
  },
});
```

## Nommage des événements

- Past-tense centré sur le métier : `reservationConfirmed`, `addressSubmitted`, `pageOpened`, `filtersCleared`.
- **Pas** d'impératif technique : éviter `clearAndFetch`, `saveDraftAndPatch`, `loadEverything`.
- Un événement décrit **ce qui s'est passé du point de vue utilisateur**, pas la liste des effets à produire.

## Exceptions tolérées

Un seul cas où dispatcher deux actions à la suite dans un composant est acceptable :

- **Form local UI réinitialisé après mutation réussie** : `await dispatch(thunk()).unwrap(); setLocalState(...)`. Ici la deuxième « action » n'est pas un dispatch Redux, c'est du state local React.

Tout le reste passe par un événement unique + `extraReducers` / listener.

## Règles strictes

1. **Un handler = un dispatch.** Si tu en écris deux, crée un événement métier qui les regroupe.
2. **Les composants ne connaissent pas les conséquences.** Ils nomment l'intention, point.
3. **Les enchaînements API vivent dans les listeners**, jamais dans les composants ni dans les `useEffect`.
4. **Plusieurs écouteurs pour un même événement, oui ; plusieurs événements pour une même intention, non.**
5. **Pas de `await dispatch(a); dispatch(b);`** dans un handler — l'attente d'un thunk pour en lancer un autre est un listener.