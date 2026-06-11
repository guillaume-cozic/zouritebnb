import React, { useEffect, useMemo } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchLocalities } from '../../geography/GeographySlice';
import { selectLocalities } from '../../geography/GeographySelectors';
import { normalizeLocality } from '../../geography/normalizeLocality';

interface Props {
  value: string;
  open: boolean;
  onSelect: (city: string) => void;
}

const LocalitySuggestions: React.FC<Props> = ({ value, open, onSelect }) => {
  const dispatch = useAppDispatch();
  const localities = useAppSelector(selectLocalities);

  useEffect(() => {
    if (localities.length === 0) {
      dispatch(fetchLocalities('RODRIGUES'));
    }
  }, [dispatch, localities.length]);

  const filtered = useMemo(() => {
    const q = normalizeLocality(value);
    if (!q) return localities;
    return localities.filter((loc) => normalizeLocality(loc.name).includes(q));
  }, [value, localities]);

  if (!open || filtered.length === 0) return null;

  return (
    <ul
      role="listbox"
      className="absolute z-30 top-full mt-1 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-auto py-1"
    >
      {filtered.map((loc) => (
        <li key={loc.id}>
          <button
            type="button"
            role="option"
            aria-selected={value === loc.name}
            onMouseDown={(e) => {
              e.preventDefault();
              onSelect(loc.name);
            }}
            className="w-full text-left px-3.5 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors flex items-center gap-2"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="text-gray-400 flex-shrink-0"
            >
              <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
              <circle cx="12" cy="10" r="3" />
            </svg>
            {loc.name}
          </button>
        </li>
      ))}
    </ul>
  );
};

export default LocalitySuggestions;
