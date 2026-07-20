import React from 'react';
import { useTranslation } from 'react-i18next';
import { blogWhereToStayUrl } from '../../../i18n/blogUrl';

const ZONES = [
  { key: 'portMathurin', slug: 'port-mathurin' },
  { key: 'anseAuxAnglais', slug: 'anse-aux-anglais' },
  { key: 'pointeCoton', slug: 'pointe-coton' },
  { key: 'mourouk', slug: 'mourouk' },
  { key: 'coteOuest', slug: 'cote-ouest' },
  { key: 'centre', slug: 'centre' },
] as const;

/**
 * Section éditoriale en bas de l'accueil : présente l'île, les zones où
 * dormir (maillage vers les guides du blog) et la réservation en direct.
 */
const AboutRodriguesSection: React.FC = () => {
  const { t, i18n } = useTranslation();

  return (
    <section className="py-16 bg-gray-50/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 className="text-3xl font-bold text-gray-900 mb-6">{t('about.title')}</h2>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
          <p className="text-gray-600 leading-relaxed">{t('about.intro1')}</p>
          <p className="text-gray-600 leading-relaxed">{t('about.intro2')}</p>
        </div>

        <h3 className="text-xl font-semibold text-gray-900 mb-2">{t('about.zonesTitle')}</h3>
        <p className="text-gray-600 mb-6">{t('about.zonesIntro')}</p>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-12">
          {ZONES.map(({ key, slug }) => (
            <a
              key={slug}
              href={blogWhereToStayUrl(i18n.language, slug)}
              className="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all"
            >
              <span className="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                {t(`about.zones.${key}.name`)}
              </span>
              <p className="text-sm text-gray-500 mt-1 leading-relaxed">{t(`about.zones.${key}.blurb`)}</p>
            </a>
          ))}
        </div>

        <h3 className="text-xl font-semibold text-gray-900 mb-2">{t('about.bookingTitle')}</h3>
        <p className="text-gray-600 leading-relaxed max-w-3xl">{t('about.booking')}</p>
      </div>
    </section>
  );
};

export default AboutRodriguesSection;
