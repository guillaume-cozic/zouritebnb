/** Loading placeholder: a stack of pulsing rows. */
export function ListSkeleton({ rows = 6 }: { rows?: number }) {
  return (
    <div className="space-y-3" role="status" aria-label="Chargement">
      {Array.from({ length: rows }, (_, i) => (
        <div key={i} className="h-12 animate-pulse rounded-lg bg-surface-200" />
      ))}
    </div>
  );
}
