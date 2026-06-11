import React, { useEffect, useRef } from 'react';
import { cn } from './cn';

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title?: React.ReactNode;
  /** Right-aligned action row rendered under a separator. */
  footer?: React.ReactNode;
  /** Max width of the panel. */
  size?: 'md' | 'lg';
  className?: string;
  children: React.ReactNode;
}

/**
 * Single modal primitive: one z-index for the whole app, Escape + overlay
 * click to close, dialog semantics and initial focus on the panel.
 */
export const Modal: React.FC<ModalProps> = ({
  open,
  onClose,
  title,
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
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
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
          size === 'md' ? 'max-w-md' : 'max-w-2xl',
          className
        )}
        onClick={(e) => e.stopPropagation()}
      >
        {title && (
          <div className="px-6 py-4 border-b border-surface-100">
            <h2 className="text-lg font-semibold text-surface-900">{title}</h2>
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
