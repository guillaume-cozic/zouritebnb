export function ErrorMessage({ message }: { message: string | null }) {
  return (
    <div className="rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">
      {message ?? 'Une erreur est survenue'}
    </div>
  );
}
