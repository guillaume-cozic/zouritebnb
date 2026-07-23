import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { sendContactMessage, contactPageLeft } from '../ContactSlice';
import { selectContactStatus, selectContactError } from '../ContactSelectors';
import { Button, Field, Input, Textarea } from '../../../components/ui';
import Footer from '../../../components/Footer';

const ContactPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const status = useAppSelector(selectContactStatus);
  const error = useAppSelector(selectContactError);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');

  useEffect(() => {
    return () => {
      dispatch(contactPageLeft());
    };
  }, [dispatch]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    dispatch(sendContactMessage({ name, email, subject, message }));
  };

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-b from-primary-50/30 via-white to-white -mt-16 pt-16">
      <div className="flex-1 w-full max-w-xl mx-auto px-4 sm:px-6 py-10">
        <header className="mb-8">
          <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary-700">
            <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
            {t('contact.eyebrow')}
          </div>
          <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">
            {t('contact.title')}
          </h1>
          <p className="text-gray-500 mt-1">{t('contact.subtitle')}</p>
        </header>

        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
          {status === 'succeeded' ? (
            <div className="text-center py-4">
              <span className="inline-flex w-16 h-16 rounded-full bg-success-100 text-success-600 items-center justify-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="32"
                  height="32"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                >
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </span>
              <h2 className="mt-5 text-2xl font-bold text-gray-900">
                {t('contact.success.title')}
              </h2>
              <p className="mt-2 text-gray-600">{t('contact.success.message')}</p>
              <div className="mt-8">
                <Link
                  to="/"
                  className="inline-flex items-center justify-center rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 h-11 px-6 transition-all"
                >
                  {t('contact.success.backHome')}
                </Link>
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-5">
              <Field label={t('contact.nameLabel')}>
                <Input
                  name="name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder={t('contact.namePlaceholder') as string}
                  autoComplete="name"
                  required
                />
              </Field>
              <Field label={t('contact.emailLabel')}>
                <Input
                  type="email"
                  name="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder={t('contact.emailPlaceholder') as string}
                  autoComplete="email"
                  required
                />
              </Field>
              <Field label={t('contact.subjectLabel')}>
                <Input
                  name="subject"
                  value={subject}
                  onChange={(e) => setSubject(e.target.value)}
                  placeholder={t('contact.subjectPlaceholder') as string}
                  required
                />
              </Field>
              <Field label={t('contact.messageLabel')}>
                <Textarea
                  name="message"
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  placeholder={t('contact.messagePlaceholder') as string}
                  rows={6}
                  required
                />
              </Field>

              {status === 'failed' && error && (
                <div
                  role="alert"
                  className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700"
                >
                  {error}
                </div>
              )}

              <Button type="submit" loading={status === 'loading'} className="w-full">
                {t('contact.submit')}
              </Button>
            </form>
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
};

export default ContactPage;
