import { accommodationIdFromSlug, accommodationPath, slugify } from './accommodationUrl';

const ID = '040944a5-647e-5db5-96d4-e75d8eec6074';

describe('slugify', () => {
  test('minuscules, accents translittérés, ponctuation en tirets', () => {
    expect(slugify('* Kyo Villa *')).toBe('kyo-villa');
    expect(slugify("Vue sur l'océan — Rivière Cocos")).toBe('vue-sur-l-ocean-riviere-cocos');
    expect(slugify('Cœur créole')).toBe('coeur-creole');
  });
});

describe('accommodationPath', () => {
  test('construit /hebergements/<slug>--<uuid> avec titre et ville', () => {
    expect(accommodationPath({ id: ID, title: '* Kyo Villa *', city: 'Baie Diamant' })).toBe(
      `/hebergements/kyo-villa-baie-diamant--${ID}`
    );
  });

  test("retombe sur l'ancienne URL sans titre ni ville", () => {
    expect(accommodationPath({ id: ID })).toBe(`/accommodations/${ID}`);
  });
});

describe('accommodationIdFromSlug', () => {
  test("extrait l'UUID en fin de slug, quel que soit le texte devant", () => {
    expect(accommodationIdFromSlug(`kyo-villa-baie-diamant--${ID}`)).toBe(ID);
    expect(accommodationIdFromSlug(`nimporte-quoi--${ID}`)).toBe(ID);
    expect(accommodationIdFromSlug(ID)).toBe(ID);
  });

  test('null quand aucun UUID ne termine le slug', () => {
    expect(accommodationIdFromSlug('kyo-villa')).toBeNull();
    expect(accommodationIdFromSlug(undefined)).toBeNull();
  });
});
