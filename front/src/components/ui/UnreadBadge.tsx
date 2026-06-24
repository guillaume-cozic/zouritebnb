import React from 'react';
import { cn } from './cn';

export interface UnreadBadgeProps {
  /** Number of unread messages; the badge renders nothing when 0 or less. */
  count: number;
  className?: string;
}

/** Small red pill showing an unread-message count (capped at "9+"). */
export const UnreadBadge: React.FC<UnreadBadgeProps> = ({ count, className }) => {
  if (count <= 0) return null;

  return (
    <span
      className={cn(
        'min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center',
        className
      )}
    >
      {count > 9 ? '9+' : count}
    </span>
  );
};

export default UnreadBadge;
