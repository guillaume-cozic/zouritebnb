import type { ReactNode } from 'react';
import { cn } from './cn';

interface CardProps {
  className?: string;
  children: ReactNode;
}

/** White rounded panel matching the public front surface style. */
export function Card({ className, children }: CardProps) {
  return (
    <div
      className={cn(
        'rounded-2xl border border-surface-200 bg-white shadow-sm',
        className
      )}
    >
      {children}
    </div>
  );
}

interface PageHeaderProps {
  title: string;
  subtitle?: string;
  count?: number;
  action?: ReactNode;
}

/** Standard list-page header: bold title, optional subtitle/count and right-aligned action. */
export function PageHeader({ title, subtitle, count, action }: PageHeaderProps) {
  return (
    <div className="flex flex-wrap items-end justify-between gap-4">
      <div>
        <div className="flex items-center gap-3">
          <h1 className="text-2xl font-bold tracking-tight text-surface-900">{title}</h1>
          {count !== undefined && (
            <span className="rounded-full bg-surface-100 px-2.5 py-0.5 text-sm font-semibold text-surface-600">
              {count}
            </span>
          )}
        </div>
        {subtitle && <p className="mt-1 text-sm text-surface-500">{subtitle}</p>}
      </div>
      {action}
    </div>
  );
}
