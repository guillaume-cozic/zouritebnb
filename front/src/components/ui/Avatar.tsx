import React from 'react';
import { cn } from './cn';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

export interface AvatarProps {
  /** Relative API URL (e.g. /uploads/photos/..), or null/undefined to fall back to the initial. */
  avatarUrl?: string | null;
  /** Display name — drives the alt text and the initial shown when there is no photo. */
  name: string;
  /** Tailwind size classes (width + height). */
  sizeClassName?: string;
  /** Font-size class for the initial fallback. */
  textClassName?: string;
  /** Styling for the initial fallback badge (background/text colour). */
  fallbackClassName?: string;
  /** Extra classes applied to both the image and the fallback (e.g. ring/border). */
  className?: string;
}

/** User avatar: shows the uploaded photo when available, otherwise a coloured initial badge. */
export const Avatar: React.FC<AvatarProps> = ({
  avatarUrl,
  name,
  sizeClassName = 'w-9 h-9',
  textClassName = 'text-sm',
  fallbackClassName = 'bg-primary-600 text-white',
  className,
}) => {
  const initial = name.trim().charAt(0).toUpperCase() || '?';

  if (avatarUrl) {
    return (
      <img
        src={`${API_BASE}${avatarUrl}`}
        alt={name}
        className={cn(sizeClassName, 'rounded-full object-cover', className)}
      />
    );
  }

  return (
    <span
      className={cn(
        sizeClassName,
        textClassName,
        fallbackClassName,
        'rounded-full flex items-center justify-center font-semibold',
        className
      )}
    >
      {initial}
    </span>
  );
};

export default Avatar;
