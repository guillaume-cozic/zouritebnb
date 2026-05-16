import React, { useEffect } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchConversationById } from '../ConversationSlice';
import {
  selectCurrentConversation,
  selectCurrentConversationStatus,
  selectCurrentConversationError,
} from '../ConversationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import ConversationThread from './ConversationThread';

const ConversationDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const dispatch = useAppDispatch();
  const conversation = useAppSelector(selectCurrentConversation);
  const status = useAppSelector(selectCurrentConversationStatus);
  const error = useAppSelector(selectCurrentConversationError);
  const user = useAppSelector(selectAuthUser);
  const { t, i18n } = useTranslation();

  useEffect(() => {
    if (id) {
      dispatch(fetchConversationById(id));
    }
  }, [dispatch, id]);

  if (!user) return null;

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  return (
    <div className="h-[calc(100vh-4rem)] flex flex-col bg-white">
      <header className="border-b border-gray-100 bg-white px-4 sm:px-8 py-3">
        <div className="max-w-3xl mx-auto">
          <Link
            to="/conversations"
            className="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-blue-600 transition-colors"
          >
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <path d="m15 18-6-6 6-6" />
            </svg>
            {t('conversation.backToList')}
          </Link>
          <div className="flex items-center justify-between gap-3 mt-1">
            <div>
              <h1 className="text-base font-semibold text-gray-900">{t('conversation.detailTitle')}</h1>
              {conversation && (
                <Link
                  to={`/accommodations/${conversation.accommodationId}`}
                  className="text-xs text-blue-600 hover:text-blue-700"
                >
                  {t('conversation.viewAccommodation')}
                </Link>
              )}
            </div>
            {conversation && (
              <span className="text-xs text-gray-400">
                {new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(conversation.createdAt))}
              </span>
            )}
          </div>
        </div>
      </header>

      <div className="flex-1 min-h-0 max-w-3xl mx-auto w-full">
        {status === 'loading' && (
          <div className="h-full flex items-center justify-center text-sm text-gray-400">
            {t('conversation.loading')}
          </div>
        )}
        {status === 'failed' && (
          <div className="h-full flex items-center justify-center text-sm text-red-600">{error}</div>
        )}
        {status === 'succeeded' && conversation && (
          <ConversationThread conversation={conversation} currentUserId={user.id} />
        )}
      </div>
    </div>
  );
};

export default ConversationDetailPage;
