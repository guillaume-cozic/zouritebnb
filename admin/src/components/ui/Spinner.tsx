export function Spinner() {
  return (
    <div className="flex justify-center py-8" role="status" aria-label="Chargement">
      <div className="h-8 w-8 animate-spin rounded-full border-4 border-surface-200 border-t-primary-600" />
    </div>
  );
}
