import React from 'react';
import { cn } from './cn';

interface CardProps {
  title?: React.ReactNode;
  /** Secondary line under the title. */
  subtitle?: React.ReactNode;
  /** Icon rendered in a rounded tile left of the title. */
  icon?: React.ReactNode;
  /** Tile colors, e.g. "bg-success-50 text-success-600". */
  iconClassName?: string;
  /** Right-aligned header content (save badge, link, counter…). */
  action?: React.ReactNode;
  className?: string;
  children: React.ReactNode;
}

/** White rounded panel with the standard header (icon tile + title + action). */
export const Card: React.FC<CardProps> = ({
  title,
  subtitle,
  icon,
  iconClassName,
  action,
  className,
  children,
}) => (
  <div className={cn('bg-white rounded-2xl border border-surface-200 shadow-sm p-6', className)}>
    {(title || action) && (
      <div className="flex items-start justify-between mb-5">
        <div className="flex items-center gap-3">
          {icon && (
            <div
              className={cn(
                'flex items-center justify-center w-9 h-9 rounded-xl',
                iconClassName ?? 'bg-surface-100 text-surface-600'
              )}
            >
              {icon}
            </div>
          )}
          <div>
            <h2 className="text-lg font-semibold text-surface-900">{title}</h2>
            {subtitle && <p className="text-xs text-surface-500">{subtitle}</p>}
          </div>
        </div>
        {action}
      </div>
    )}
    {children}
  </div>
);
