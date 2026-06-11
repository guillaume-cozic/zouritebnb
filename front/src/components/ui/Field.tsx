import React from 'react';
import { cn } from './cn';

interface FieldProps {
  label: React.ReactNode;
  /** Validation error shown under the control. */
  error?: React.ReactNode;
  /** Secondary explanation shown under the control. */
  hint?: React.ReactNode;
  className?: string;
  children: React.ReactNode;
}

/** Label + control + error/hint — the form-row pattern repeated across every page. */
export const Field: React.FC<FieldProps> = ({ label, error, hint, className, children }) => (
  <div className={cn(className)}>
    <label className="block text-sm font-medium text-surface-700 mb-1.5">{label}</label>
    {children}
    {error && <p className="mt-1 text-sm text-danger-600">{error}</p>}
    {hint && <p className="mt-2 text-xs text-surface-500">{hint}</p>}
  </div>
);
