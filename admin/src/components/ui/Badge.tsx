import type { ReactNode } from 'react';

export type BadgeVariant = 'primary' | 'surface' | 'success' | 'danger' | 'warning';

const variantClasses: Record<BadgeVariant, string> = {
  primary: 'bg-primary-100 text-primary-800',
  surface: 'bg-surface-100 text-surface-700',
  success: 'bg-success-100 text-success-800',
  danger: 'bg-danger-100 text-danger-800',
  warning: 'bg-warning-100 text-warning-800',
};

interface BadgeProps {
  variant?: BadgeVariant;
  children: ReactNode;
}

export function Badge({ variant = 'surface', children }: BadgeProps) {
  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${variantClasses[variant]}`}
    >
      {children}
    </span>
  );
}
