import React, { useState } from 'react';
import { MAX_RATING } from '../ReviewTypes';

interface Props {
  value: number;
  onChange: (value: number) => void;
  disabled?: boolean;
}

const StarRating: React.FC<Props> = ({ value, onChange, disabled = false }) => {
  const [hovered, setHovered] = useState<number | null>(null);

  return (
    <div className="flex items-center gap-1" role="radiogroup" aria-label="rating">
      {Array.from({ length: MAX_RATING }, (_, i) => i + 1).map((star) => {
        const active = (hovered ?? value) >= star;
        return (
          <button
            key={star}
            type="button"
            role="radio"
            aria-checked={value === star}
            aria-label={String(star)}
            disabled={disabled}
            onClick={() => onChange(star)}
            onMouseEnter={() => !disabled && setHovered(star)}
            onMouseLeave={() => !disabled && setHovered(null)}
            className="p-0.5 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-amber-400/40 rounded"
          >
            <svg
              width="28"
              height="28"
              viewBox="0 0 24 24"
              fill={active ? '#f59e0b' : 'none'}
              stroke={active ? '#f59e0b' : '#d1d5db'}
              strokeWidth="1.5"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="transition-colors"
            >
              <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
            </svg>
          </button>
        );
      })}
    </div>
  );
};

export default StarRating;
