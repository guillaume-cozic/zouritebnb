/** Joins truthy class fragments — tiny local alternative to clsx. */
export const cn = (...classes: Array<string | false | null | undefined>): string =>
  classes.filter(Boolean).join(' ');
