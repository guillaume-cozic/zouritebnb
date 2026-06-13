import { cn } from './cn';

interface PaginationProps {
  page: number;
  itemsPerPage: number;
  totalItems: number;
  onPageChange: (page: number) => void;
}

/**
 * Builds the list of page tokens to display: first/last page, a window around the
 * current page, and `'…'` gaps. e.g. [1, '…', 4, 5, 6, '…', 20].
 */
function buildPages(current: number, total: number): Array<number | 'gap'> {
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  const pages: Array<number | 'gap'> = [1];
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);

  if (start > 2) pages.push('gap');
  for (let p = start; p <= end; p += 1) pages.push(p);
  if (end < total - 1) pages.push('gap');

  pages.push(total);
  return pages;
}

export function Pagination({ page, itemsPerPage, totalItems, onPageChange }: PaginationProps) {
  const pageCount = Math.max(1, Math.ceil(totalItems / itemsPerPage));
  if (pageCount <= 1) return null;

  const from = (page - 1) * itemsPerPage + 1;
  const to = Math.min(page * itemsPerPage, totalItems);
  const pages = buildPages(page, pageCount);

  const arrowClass =
    'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-surface-300 bg-white px-2.5 text-sm font-medium text-surface-600 transition-colors hover:bg-surface-50 disabled:cursor-not-allowed disabled:opacity-40';

  return (
    <nav
      className="flex flex-wrap items-center justify-between gap-3"
      aria-label="Pagination"
    >
      <p className="text-sm text-surface-500">
        <span className="font-medium text-surface-700">
          {from}–{to}
        </span>{' '}
        sur <span className="font-medium text-surface-700">{totalItems}</span>
      </p>

      <div className="flex items-center gap-1">
        <button
          type="button"
          className={arrowClass}
          onClick={() => onPageChange(page - 1)}
          disabled={page <= 1}
          aria-label="Page précédente"
        >
          ‹
        </button>

        {pages.map((token, index) =>
          token === 'gap' ? (
            <span
              key={`gap-${index}`}
              className="inline-flex h-9 w-9 items-center justify-center text-sm text-surface-400"
            >
              …
            </span>
          ) : (
            <button
              key={token}
              type="button"
              onClick={() => onPageChange(token)}
              aria-current={token === page ? 'page' : undefined}
              className={cn(
                'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2.5 text-sm font-medium transition-colors',
                token === page
                  ? 'bg-primary-600 text-white'
                  : 'border border-surface-300 bg-white text-surface-600 hover:bg-surface-50'
              )}
            >
              {token}
            </button>
          )
        )}

        <button
          type="button"
          className={arrowClass}
          onClick={() => onPageChange(page + 1)}
          disabled={page >= pageCount}
          aria-label="Page suivante"
        >
          ›
        </button>
      </div>
    </nav>
  );
}
