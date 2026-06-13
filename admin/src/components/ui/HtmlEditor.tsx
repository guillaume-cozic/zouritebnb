import { useState } from 'react';
import { cn } from './cn';

interface HtmlEditorProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

const PREVIEW_PROSE = cn(
  'text-sm leading-relaxed text-surface-700',
  '[&_h1]:mb-2 [&_h1]:text-2xl [&_h1]:font-bold [&_h1]:text-surface-900',
  '[&_h2]:mb-2 [&_h2]:mt-5 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-surface-900',
  '[&_h3]:mb-1 [&_h3]:mt-4 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-surface-900',
  '[&_p]:my-3 [&_ul]:my-3 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:my-3 [&_ol]:list-decimal [&_ol]:pl-5',
  '[&_li]:my-1 [&_a]:text-primary-600 [&_a]:underline [&_strong]:font-semibold [&_em]:italic',
  '[&_img]:my-3 [&_img]:rounded-lg [&_blockquote]:border-l-2 [&_blockquote]:border-surface-300 [&_blockquote]:pl-3 [&_blockquote]:text-surface-500'
);

/**
 * Large raw-HTML field with a rendered preview tab. The HTML is stored and served
 * verbatim (no normalization), which suits articles generated outside the app.
 */
export function HtmlEditor({ value, onChange, placeholder }: HtmlEditorProps) {
  const [tab, setTab] = useState<'edit' | 'preview'>('edit');

  const tabClass = (active: boolean) =>
    cn(
      'px-4 py-2 text-sm font-medium transition-colors',
      active
        ? 'border-b-2 border-primary-600 text-primary-700'
        : 'border-b-2 border-transparent text-surface-500 hover:text-surface-800'
    );

  return (
    <div className="overflow-hidden rounded-xl border border-surface-200 bg-white focus-within:border-primary-400 focus-within:ring-2 focus-within:ring-primary-500/20">
      <div className="flex items-center justify-between border-b border-surface-200 bg-surface-50 pr-3">
        <div className="flex">
          <button type="button" className={tabClass(tab === 'edit')} onClick={() => setTab('edit')}>
            Éditer
          </button>
          <button
            type="button"
            className={tabClass(tab === 'preview')}
            onClick={() => setTab('preview')}
          >
            Aperçu
          </button>
        </div>
        <span className="text-xs text-surface-400">HTML</span>
      </div>

      {tab === 'edit' ? (
        <textarea
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          spellCheck={false}
          className="block min-h-[28rem] w-full resize-y bg-white px-4 py-3 font-mono text-sm text-surface-900 placeholder-surface-400 focus:outline-none"
        />
      ) : (
        <div className="min-h-[28rem] px-4 py-3">
          {value.trim() === '' ? (
            <p className="text-sm text-surface-400">Aperçu vide — collez ou saisissez du HTML.</p>
          ) : (
            <div className={PREVIEW_PROSE} dangerouslySetInnerHTML={{ __html: value }} />
          )}
        </div>
      )}
    </div>
  );
}
