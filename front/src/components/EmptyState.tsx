import React from 'react';

interface EmptyStateProps {
  /** Icon shown inside the rounded badge. Defaults to a generic "inbox" glyph. */
  icon?: React.ReactNode;
  title: string;
  description?: string;
  /** Optional call-to-action (e.g. a Link/button) rendered below the text. */
  action?: React.ReactNode;
  /** `card` wraps the content in a dashed bordered panel; `plain` renders it bare (e.g. inside an existing card). */
  variant?: 'card' | 'plain';
  className?: string;
}

const EmptyState: React.FC<EmptyStateProps> = ({
  icon,
  title,
  description,
  action,
  variant = 'card',
  className = '',
}) => {
  const wrapperClasses =
    variant === 'card'
      ? 'rounded-3xl bg-white border border-dashed border-gray-200 px-6 py-16'
      : 'px-6 py-16';

  return (
    <div className={`text-center ${wrapperClasses} ${className}`.trim()}>
      <div className="mx-auto w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mb-4 text-blue-500">
        {icon ?? <InboxIcon />}
      </div>
      <h3 className="text-base font-semibold text-gray-900">{title}</h3>
      {description && (
        <p className="text-sm text-gray-500 max-w-sm mx-auto mt-1.5">{description}</p>
      )}
      {action && <div className="mt-6">{action}</div>}
    </div>
  );
};

const iconProps = {
  width: 24,
  height: 24,
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 1.5,
  strokeLinecap: 'round' as const,
  strokeLinejoin: 'round' as const,
};

export const HomeIcon: React.FC = () => (
  <svg {...iconProps}>
    <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
  </svg>
);

export const CalendarIcon: React.FC = () => (
  <svg {...iconProps}>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <path d="M16 2v4M8 2v4M3 10h18" />
  </svg>
);

export const InboxIcon: React.FC = () => (
  <svg {...iconProps}>
    <path d="M22 12h-6l-2 3h-4l-2-3H2" />
    <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
  </svg>
);

export default EmptyState;
