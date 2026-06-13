import { useEffect, useState } from 'react';
import { useDebouncedValue } from './useDebouncedValue';

export interface CollectionQuery {
  page: number;
  search: string;
  /** The active categorical filter, or '' when "all" is selected. */
  filter: string;
}

/**
 * Drives the search/filter/page state of an admin list page and calls `fetchPage`
 * whenever the (debounced) query changes. Changing the search or filter resets to page 1.
 */
export function useCollectionQuery(fetchPage: (query: CollectionQuery) => void) {
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState('all');
  const [page, setPage] = useState(1);
  const debouncedSearch = useDebouncedValue(search, 300);

  useEffect(() => {
    fetchPage({ page, search: debouncedSearch.trim(), filter: filter === 'all' ? '' : filter });
    // `fetchPage` is provided as a stable callback by the caller.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, debouncedSearch, filter]);

  return {
    search,
    filter,
    page,
    setPage,
    onSearchChange: (value: string) => {
      setSearch(value);
      setPage(1);
    },
    onFilterChange: (value: string) => {
      setFilter(value);
      setPage(1);
    },
  };
}
