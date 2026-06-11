export function EmptyState({ message }: { message: string }) {
  return (
    <div className="rounded-lg border border-dashed border-surface-300 bg-white px-4 py-12 text-center text-sm text-surface-500">
      {message}
    </div>
  );
}
