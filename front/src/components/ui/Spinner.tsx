import React from 'react';
import { cn } from './cn';

export const Spinner: React.FC<{ size?: number; className?: string }> = ({ size = 14, className }) => (
  <svg
    className={cn('animate-spin', className)}
    xmlns="http://www.w3.org/2000/svg"
    width={size}
    height={size}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2.5"
    aria-hidden="true"
  >
    <path d="M21 12a9 9 0 11-6.219-8.56" />
  </svg>
);
