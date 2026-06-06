/**
 * Consumer-side contract test (compile-time).
 *
 * The backend publishes its OpenAPI schema as `API/openapi.json`; the types in
 * `./schema.ts` are generated from it (`npm run gen:api-types`). This file pins
 * the hand-written domain types used across the app to that generated contract:
 * if the backend renames/removes a field or changes its type, `tsc` fails here
 * until the front is updated — caught at build time, never in production.
 *
 * The assertions check that each hand-written type is a *valid subset* of what
 * the contract permits (the consumer direction): the front never expects a field
 * the contract forbids, nor a narrower-than-allowed type.
 *
 * This module has no runtime behaviour — it exists purely for the type checker.
 * Run `npm run typecheck` (or `npm run build`) to evaluate it.
 */
import type { components } from './schema';
import type { AccommodationListItem } from '../features/homepage/HomepageTypes';

/** Fails to compile unless `T` is exactly `true`. */
type Expect<T extends true> = T;

/** True only when `T` is `never` (i.e. no leftover keys). */
type IsNever<T> = [T] extends [never] ? true : false;

/** True when every shared field of `Front` is assignable to the contract's field. */
type FieldsCompatible<Front, Contract> = {
  [K in keyof Front & keyof Contract]: NonNullable<Front[K]> extends NonNullable<Contract[K]>
    ? true
    : false;
}[keyof Front & keyof Contract] extends true
  ? true
  : false;

// --- Accommodation list item (homepage listing / map) -----------------------

type AccommodationListContract =
  components['schemas']['AccommodationEntity.jsonld-accommodation.list'];

// 1. Every field the front relies on must still exist in the published contract.
type _ListItemKeysExistInContract = Expect<
  IsNever<Exclude<keyof AccommodationListItem, keyof AccommodationListContract>>
>;

// 2. Each shared field's type must be compatible with the contract.
type _ListItemTypesMatchContract = Expect<
  FieldsCompatible<AccommodationListItem, AccommodationListContract>
>;