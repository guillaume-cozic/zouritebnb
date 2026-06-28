import React from 'react';
import Footer from '../../../components/Footer';

interface LegalPageLayoutProps {
  title: string;
  lastUpdated: string;
  children: React.ReactNode;
}

/**
 * Shared shell for the legal pages (CGU, CGV). Renders a centered, readable
 * column with consistent heading/paragraph rhythm so each document only has to
 * provide its sections. The document title is set centrally by DocumentTitle.
 */
const LegalPageLayout: React.FC<LegalPageLayoutProps> = ({ title, lastUpdated, children }) => (
  <div className="min-h-[calc(100vh-4rem)] flex flex-col bg-gray-50/50">
    <div className="flex-1 w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <header className="mb-10">
        <h1 className="text-4xl font-bold text-gray-900">{title}</h1>
        <p className="mt-3 text-sm text-gray-500">Dernière mise à jour : {lastUpdated}</p>
      </header>
      <div className="legal-prose space-y-8 text-gray-700 leading-relaxed">{children}</div>
    </div>
    <Footer />
  </div>
);

/** A numbered section with a heading and free-form body. */
export const LegalSection: React.FC<{ id: string; title: string; children: React.ReactNode }> = ({
  id,
  title,
  children,
}) => (
  <section id={id} className="scroll-mt-20">
    <h2 className="text-xl font-semibold text-gray-900 mb-3">{title}</h2>
    <div className="space-y-3">{children}</div>
  </section>
);

export default LegalPageLayout;
