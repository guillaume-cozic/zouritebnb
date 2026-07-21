import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import EmptyState from './EmptyState';

const NotFoundPage: React.FC = () => {
  const { t } = useTranslation();

  return (
    <div className="max-w-2xl mx-auto px-4 py-16">
      <EmptyState
        title={t('notFound.title')}
        description={t('notFound.message')}
        action={
          <Link
            to="/"
            className="inline-flex items-center justify-center gap-2 h-11 px-5 text-sm font-medium rounded-xl bg-primary-600 text-white hover:bg-primary-700 transition-colors"
          >
            {t('notFound.backHome')}
          </Link>
        }
      />
    </div>
  );
};

export default NotFoundPage;
