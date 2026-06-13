import type { ReactNode } from 'react';
import { PageHeader } from './ui/Card';
import { SearchInput } from './ui/SearchInput';
import { FilterChips, type FilterChipOption } from './ui/FilterChips';
import { ListSkeleton } from './ui/Skeleton';
import { ErrorMessage } from './ui/ErrorMessage';
import { EmptyState } from './ui/EmptyState';
import { Pagination } from './ui/Pagination';

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface ListPageProps {
  title: string;
  subtitle?: string;
  count: number;
  search: string;
  onSearchChange: (value: string) => void;
  searchPlaceholder?: string;
  filterOptions: FilterChipOption[];
  filterValue: string;
  onFilterChange: (value: string) => void;
  status: Status;
  error: string | null;
  isEmpty: boolean;
  emptyMessage: string;
  page: number;
  itemsPerPage: number;
  onPageChange: (page: number) => void;
  /** Optional action rendered on the right of the page header (e.g. a "New" button). */
  headerAction?: ReactNode;
  /** Optional content rendered between the header and the toolbar (e.g. a create form). */
  banner?: ReactNode;
  /** The table or card grid rendered once data is loaded. */
  children: ReactNode;
}

/**
 * Shared scaffold for the admin list pages: header with a result count, a search +
 * filter toolbar, loading/error/empty handling and a numbered pagination footer.
 */
export function ListPage({
  title,
  subtitle,
  count,
  search,
  onSearchChange,
  searchPlaceholder,
  filterOptions,
  filterValue,
  onFilterChange,
  status,
  error,
  isEmpty,
  emptyMessage,
  page,
  itemsPerPage,
  onPageChange,
  headerAction,
  banner,
  children,
}: ListPageProps) {
  const loading = status === 'loading' || status === 'idle';

  return (
    <div className="space-y-6">
      <PageHeader
        title={title}
        subtitle={subtitle}
        count={status === 'succeeded' ? count : undefined}
        action={headerAction}
      />

      {banner}

      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <SearchInput value={search} onChange={onSearchChange} placeholder={searchPlaceholder} />
        <FilterChips options={filterOptions} value={filterValue} onChange={onFilterChange} />
      </div>

      {loading ? (
        <ListSkeleton />
      ) : status === 'failed' ? (
        <ErrorMessage message={error} />
      ) : isEmpty ? (
        <EmptyState message={emptyMessage} />
      ) : (
        <div className="space-y-5">
          {children}
          <Pagination
            page={page}
            itemsPerPage={itemsPerPage}
            totalItems={count}
            onPageChange={onPageChange}
          />
        </div>
      )}
    </div>
  );
}
