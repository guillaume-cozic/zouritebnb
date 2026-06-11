export interface FilterChipOption {
  value: string;
  label: string;
}

interface FilterChipsProps {
  options: FilterChipOption[];
  value: string;
  onChange: (value: string) => void;
}

export function FilterChips({ options, value, onChange }: FilterChipsProps) {
  return (
    <div className="flex flex-wrap gap-2">
      {options.map((option) => {
        const active = option.value === value;
        return (
          <button
            key={option.value}
            type="button"
            onClick={() => onChange(option.value)}
            className={`rounded-full px-3 py-1 text-sm font-medium transition-colors ${
              active
                ? 'bg-primary-600 text-white'
                : 'bg-white text-surface-600 border border-surface-300 hover:bg-surface-100'
            }`}
          >
            {option.label}
          </button>
        );
      })}
    </div>
  );
}
