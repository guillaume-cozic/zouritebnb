import React, { useEffect, useRef } from 'react';
import { cn } from './cn';

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title?: React.ReactNode;
  /** Secondary line under the title. */
  subtitle?: React.ReactNode;
  /** Right-aligned header content (counter, badge, close button…). */
  action?: React.ReactNode;
  /** Right-aligned action row rendered under a separator. */
  footer?: React.ReactNode;
  /** Max width of the panel. */
  size?: 'sm' | 'md' | 'lg';
  className?: string;
  children: React.ReactNode;
}

const SIZES = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-2xl',
};

/**
 * Single modal primitive: one z-index for the whole app, Escape + overlay
 * click to close, dialog semantics and initial focus on the panel. The
 * overlay scrolls when the panel is taller than the viewport.
 */
export const Modal: React.FC<ModalProps> = ({
  open,
  onClose,
  title,
  subtitle,
  action,
  footer,
  size = 'md',
  className,
  children,
}) => {
  const panelRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', onKeyDown);
    panelRef.current?.focus();
    return () => document.removeEventListener('keydown', onKeyDown);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 overflow-y-auto"
      onClick={onClose}
      role="presentation"
    >
      <div
        ref={panelRef}
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        className={cn(
          'bg-white rounded-xl shadow-xl w-full focus:outline-none',
          SIZES[size],
          className
        )}
        onClick={(e) => e.stopPropagation()}
      >
        {(title || action) && (
          <div className="px-6 py-4 border-b border-surface-100 flex items-start justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold text-surface-900">{title}</h2>
              {subtitle && <div className="text-sm text-surface-500 mt-0.5">{subtitle}</div>}
            </div>
            {action}
          </div>
        )}
        <div className="px-6 py-5">{children}</div>
        {footer && (
          <div className="px-6 py-4 border-t border-surface-100 flex justify-end gap-2">{footer}</div>
        )}
      </div>
    </div>
  );
};
