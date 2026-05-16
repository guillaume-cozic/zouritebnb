import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchConversationsForUser } from '../ConversationSlice';
import {
  selectConversations,
  selectConversationsStatus,
  selectConversationsError,
} from '../ConversationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import ConversationListItem from './ConversationListItem';

const ConversationsListPage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const user = useAppSelector(selectAuthUser);
  const conversations = useAppSelector(selectConversations);
  const status = useAppSelector(selectConversationsStatus);
  const error = useAppSelector(selectConversationsError);

  useEffect(() => {
    if (user) {
      dispatch(fetchConversationsForUser(user.id));
    }
  }, [dispatch, user]);

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  return (
    <div className="min-h-screen bg-gray-50/50">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <header className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 tracking-tight">{t('conversation.inboxTitle')}</h1>
          <p className="text-gray-500 mt-2">{t('conversation.inboxSubtitle')}</p>
        </header>

        {status === 'loading' && (
          <div className="space-y-2">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-16 rounded-xl bg-white border border-gray-100 animate-pulse" />
            ))}
          </div>
        )}

        {status === 'failed' && (
          <div className="text-red-600 text-sm">{error}</div>
        )}

        {status === 'succeeded' && conversations.length === 0 && (
          <div className="rounded-2xl bg-white border border-gray-100 px-8 py-16 text-center">
            <div className="mx-auto w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mb-4">
              <svg className="text-blue-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
            </div>
            <p className="text-gray-500 max-w-sm mx-auto">{t('conversation.empty')}</p>
          </div>
        )}

        {conversations.length > 0 && (
          <div className="space-y-1 bg-white rounded-2xl border border-gray-100 p-2">
            {conversations.map((c) => (
              <ConversationListItem
                key={c.id}
                conversation={c}
                to={`/conversations/${c.id}`}
                locale={locale}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default ConversationsListPage;
