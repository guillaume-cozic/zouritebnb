import React from 'react';
import { cn } from './cn';

/** Shared look of all form controls — the single source the app previously duplicated. */
export const inputBaseClass =
  'w-full h-11 rounded-xl border border-surface-200 bg-surface-50 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all';

export const Input = React.forwardRef<HTMLInputElement, React.InputHTMLAttributes<HTMLInputElement>>(
  ({ className, ...rest }, ref) => (
    <input ref={ref} className={cn(inputBaseClass, className)} {...rest} />
  )
);
Input.displayName = 'Input';

export const Textarea = React.forwardRef<HTMLTextAreaElement, React.TextareaHTMLAttributes<HTMLTextAreaElement>>(
  ({ className, ...rest }, ref) => (
    <textarea ref={ref} className={cn(inputBaseClass, 'h-auto py-3', className)} {...rest} />
  )
);
Textarea.displayName = 'Textarea';

export const Select = React.forwardRef<HTMLSelectElement, React.SelectHTMLAttributes<HTMLSelectElement>>(
  ({ className, ...rest }, ref) => (
    <select ref={ref} className={cn(inputBaseClass, className)} {...rest} />
  )
);
Select.displayName = 'Select';
