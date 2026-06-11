import type { ReactNode } from 'react';

export function Table({ children }: { children: ReactNode }) {
  return (
    <div className="overflow-x-auto rounded-lg border border-surface-200 bg-white shadow-sm">
      <table className="min-w-full divide-y divide-surface-200">{children}</table>
    </div>
  );
}

export function THead({ children }: { children: ReactNode }) {
  return (
    <thead className="bg-surface-50">
      <tr>{children}</tr>
    </thead>
  );
}

export function TH({ children }: { children?: ReactNode }) {
  return (
    <th
      scope="col"
      className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500"
    >
      {children}
    </th>
  );
}

export function TBody({ children }: { children: ReactNode }) {
  return <tbody className="divide-y divide-surface-100 bg-white">{children}</tbody>;
}

export function TR({ children }: { children: ReactNode }) {
  return <tr className="hover:bg-surface-50">{children}</tr>;
}

export function TD({ children }: { children?: ReactNode }) {
  return <td className="whitespace-nowrap px-4 py-3 text-sm text-surface-700">{children}</td>;
}
