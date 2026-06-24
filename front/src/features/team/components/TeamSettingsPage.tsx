import React, { useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  updateTeamFavoriteProject,
  inviteCoHost,
  cancelTeamInvitation,
  teamSettingsPageOpened,
  bankAccountEdited,
} from '../TeamSlice';
import {
  selectCurrentTeam,
  selectTeamError,
  selectTeamStatus,
  selectTeamInvitations,
  selectInviteStatus,
  selectInviteError,
  selectBankSaveState,
  selectBankSaveError,
  selectFavoriteSaveState,
} from '../TeamSelectors';
import { selectSolidarityProjects } from '../../solidarityProject/SolidarityProjectSelectors';
import { selectAuthTeamId, selectAuthUser, selectProfileSaveState } from '../../auth/AuthSelectors';
import { profileEdited, uploadAvatar } from '../../auth/AuthSlice';
import { AuthUser } from '../../auth/AuthTypes';
import { DEFAULT_TEAM_ID, Team } from '../TeamTypes';
import { Button, Card, Field, Input, Modal, SaveIndicator, Select, Textarea } from '../../../components/ui';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

/**
 * Profile form, keyed by user id so the controlled inputs hydrate from props.
 * Every change dispatches a single `profileEdited` intent; the listener
 * debounces and saves.
 */
const ProfileSection: React.FC<{ user: AuthUser }> = ({ user }) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const profileSaveState = useAppSelector(selectProfileSaveState);

  const [firstName, setFirstName] = useState(user.firstName ?? '');
  const [lastName, setLastName] = useState(user.lastName ?? '');
  const [email, setEmail] = useState(user.email);
  const [bio, setBio] = useState(user.bio ?? '');

  const handleChange = (next: { firstName?: string; lastName?: string; email?: string; bio?: string }) => {
    const values = { firstName, lastName, email, bio, ...next };
    if (next.firstName !== undefined) setFirstName(next.firstName);
    if (next.lastName !== undefined) setLastName(next.lastName);
    if (next.email !== undefined) setEmail(next.email);
    if (next.bio !== undefined) setBio(next.bio);
    dispatch(profileEdited({ userId: user.id, ...values }));
  };

  const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) dispatch(uploadAvatar(file));
    e.target.value = '';
  };

  const initial = (firstName || user.email).charAt(0).toUpperCase();

  return (
    <Card
      title={t('team.profileTitle')}
      subtitle={t('team.profileHelp')}
      action={<SaveIndicator status={profileSaveState} />}
      className="mb-6"
    >
      <div className="flex items-center gap-4 mb-4">
        {user.avatarUrl ? (
          <img
            src={`${API_BASE}${user.avatarUrl}`}
            alt={t('team.photo') as string}
            className="w-20 h-20 rounded-full object-cover border border-gray-200"
          />
        ) : (
          <span className="w-20 h-20 rounded-full bg-primary-600 text-white flex items-center justify-center text-2xl font-semibold">
            {initial}
          </span>
        )}
        <div>
          <label className="inline-flex items-center gap-2 h-9 px-3 rounded-md border border-gray-200 bg-white hover:bg-gray-50 text-sm font-medium cursor-pointer">
            <input type="file" accept="image/jpeg,image/png,image/webp" onChange={handleAvatarChange} className="hidden" />
            {t('team.changePhoto')}
          </label>
          <p className="text-xs text-gray-500 mt-1">{t('team.photoHelp')}</p>
        </div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Field label={t('team.firstName')}>
          <Input
            type="text"
            value={firstName}
            onChange={(e) => handleChange({ firstName: e.target.value })}
          />
        </Field>
        <Field label={t('team.lastName')}>
          <Input
            type="text"
            value={lastName}
            onChange={(e) => handleChange({ lastName: e.target.value })}
          />
        </Field>
        <Field label={t('team.email')} className="md:col-span-2">
          <Input
            type="email"
            value={email}
            onChange={(e) => handleChange({ email: e.target.value })}
          />
        </Field>
        <Field label={t('team.bio')} hint={t('team.bioHelp') as string} className="md:col-span-2">
          <Textarea
            rows={4}
            value={bio}
            maxLength={2000}
            onChange={(e) => handleChange({ bio: e.target.value })}
          />
        </Field>
      </div>
    </Card>
  );
};

/**
 * Bank account form, keyed by team id. Every change dispatches a single
 * `bankAccountEdited` intent; the listener debounces, normalises and saves.
 */
const BankAccountSection: React.FC<{ team: Team }> = ({ team }) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const bankSaveState = useAppSelector(selectBankSaveState);
  const bankSaveError = useAppSelector(selectBankSaveError);

  const [iban, setIban] = useState(team.iban ?? '');
  const [bic, setBic] = useState(team.bic ?? '');
  const [holderName, setHolderName] = useState(team.bankAccountHolderName ?? '');

  const handleChange = (next: { iban?: string; bic?: string; holderName?: string }) => {
    const values = { iban, bic, holderName, ...next };
    if (next.iban !== undefined) setIban(next.iban);
    if (next.bic !== undefined) setBic(next.bic);
    if (next.holderName !== undefined) setHolderName(next.holderName);
    dispatch(bankAccountEdited({ teamId: team.id, ...values }));
  };

  return (
    <Card
      title={t('team.bankAccount.title')}
      subtitle={t('team.bankAccount.help')}
      action={<SaveIndicator status={bankSaveState} />}
      className="mt-6"
    >
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Field label={t('team.bankAccount.iban')} className="md:col-span-2">
          <Input
            type="text"
            value={iban}
            onChange={(e) => handleChange({ iban: e.target.value })}
            placeholder="FR76 3000 1007 9412 3456 7890 185"
            spellCheck={false}
            autoComplete="off"
            className="font-mono tracking-wider"
          />
        </Field>
        <Field label={t('team.bankAccount.holderName')}>
          <Input
            type="text"
            value={holderName}
            onChange={(e) => handleChange({ holderName: e.target.value })}
            placeholder={t('team.bankAccount.holderNamePlaceholder') as string}
            autoComplete="off"
          />
        </Field>
        <Field
          label={
            <>
              {t('team.bankAccount.bic')}{' '}
              <span className="text-xs font-normal text-surface-400">({t('team.bankAccount.optional')})</span>
            </>
          }
        >
          <Input
            type="text"
            value={bic}
            onChange={(e) => handleChange({ bic: e.target.value })}
            placeholder="BDFEFRPPCCT"
            spellCheck={false}
            autoComplete="off"
            className="font-mono tracking-wider"
          />
        </Field>
      </div>
      {iban.trim() !== '' && holderName.trim() === '' && (
        <p className="mt-3 text-xs text-warning-700 bg-warning-50 border border-warning-200 rounded-lg px-3 py-2">
          {t('team.bankAccount.holderRequired')}
        </p>
      )}
      {bankSaveState === 'error' && bankSaveError && (
        <p className="mt-3 text-xs text-danger-700 bg-danger-50 border border-danger-200 rounded-lg px-3 py-2">
          {bankSaveError}
        </p>
      )}
    </Card>
  );
};

const TeamSettingsPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const location = useLocation();
  const isTravelerMode = location.pathname.startsWith('/account');
  const team = useAppSelector(selectCurrentTeam);
  const teamStatus = useAppSelector(selectTeamStatus);
  const teamError = useAppSelector(selectTeamError);
  const projects = useAppSelector(selectSolidarityProjects);
  const teamId = useAppSelector(selectAuthTeamId) ?? DEFAULT_TEAM_ID;
  const user = useAppSelector(selectAuthUser);
  const invitations = useAppSelector(selectTeamInvitations);
  const inviteStatus = useAppSelector(selectInviteStatus);
  const inviteError = useAppSelector(selectInviteError);
  const favoriteSaveState = useAppSelector(selectFavoriteSaveState);

  const [inviteEmail, setInviteEmail] = useState('');
  const [invitationToCancel, setInvitationToCancel] = useState<{ id: string; email: string } | null>(null);

  useEffect(() => {
    dispatch(teamSettingsPageOpened({ teamId }));
  }, [dispatch, teamId]);

  const handleInvite = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!teamId || !inviteEmail.trim()) return;
    const result = await dispatch(inviteCoHost({ teamId, email: inviteEmail.trim() }));
    if (inviteCoHost.fulfilled.match(result)) {
      setInviteEmail('');
    }
  };

  const activeProjects = projects.filter((p) => p.status === 'active');

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <header className="mb-8 relative">
        <div className="absolute -left-4 top-0 bottom-2 w-1 bg-gradient-to-b from-primary-500 via-primary-400 to-transparent rounded-full" aria-hidden="true" />
        <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary-700">
          <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
          {t('backoffice.menu.title')}
        </div>
        <h1 className="mt-2 text-3xl font-bold text-surface-900 tracking-tight">{t('team.title')}</h1>
        <p className="text-surface-500 mt-1">{t('team.subtitle')}</p>
      </header>

      {!teamId && (
        <div className="bg-warning-50 border border-warning-200 rounded-xl p-4 text-sm text-warning-800">
          {t('team.loginRequired')}
        </div>
      )}

      {user && <ProfileSection key={user.id} user={user} />}

      {teamId && teamStatus === 'loading' && !team && (
        <div className="text-center py-12 text-surface-500">{t('homepage.loading')}</div>
      )}
      {teamStatus === 'failed' && (
        <div className="text-center py-12 text-danger-500">{teamError}</div>
      )}

      {team && !isTravelerMode && (
        <Card
          title={t('team.favoriteProjectLabel')}
          subtitle={t('team.favoriteProjectHelp')}
          action={<SaveIndicator status={favoriteSaveState} />}
        >
          <Select
            value={team.favoriteSolidarityProjectId ?? ''}
            onChange={(e) => dispatch(updateTeamFavoriteProject({
              id: team.id,
              favoriteSolidarityProjectId: e.target.value || null,
            }))}
          >
            <option value="">{t('team.favoriteProjectNone')}</option>
            {activeProjects.map((p) => (
              <option key={p.id} value={p.id}>{p.title}</option>
            ))}
          </Select>
        </Card>
      )}

      {team && !isTravelerMode && <BankAccountSection key={team.id} team={team} />}

      {team && !isTravelerMode && (
        <Card title={t('team.coHostsTitle')} subtitle={t('team.coHostsHelp')} className="mt-6">
          <form onSubmit={handleInvite} className="flex flex-col sm:flex-row gap-2 mb-4">
            <Input
              type="email"
              required
              placeholder={t('team.inviteEmailPlaceholder')}
              value={inviteEmail}
              onChange={(e) => setInviteEmail(e.target.value)}
              className="flex-1"
            />
            <Button
              type="submit"
              loading={inviteStatus === 'loading'}
              disabled={!inviteEmail.trim()}
            >
              {inviteStatus === 'loading' ? t('team.inviting') : t('team.inviteButton')}
            </Button>
          </form>

          {inviteStatus === 'succeeded' && (
            <div className="mb-4 text-sm text-success-600">{t('team.invitationSent')}</div>
          )}
          {inviteStatus === 'failed' && inviteError && (
            <div className="mb-4 text-sm text-danger-600">{inviteError}</div>
          )}

          <h3 className="text-sm font-medium text-surface-700 mb-2">{t('team.pendingInvitations')}</h3>
          {invitations.length === 0 ? (
            <p className="text-sm text-surface-400">{t('team.noPendingInvitations')}</p>
          ) : (
            <ul className="divide-y divide-surface-100 border border-surface-100 rounded-xl overflow-hidden">
              {invitations.map((inv) => (
                <li key={inv.id} className="flex items-center justify-between px-4 py-3 bg-surface-50/50">
                  <div className="flex flex-col">
                    <span className="text-sm text-surface-900">{inv.email}</span>
                    <span className="text-xs text-surface-400">
                      {new Date(inv.createdAt).toLocaleDateString()}
                    </span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800">
                      {t('team.invitationPending')}
                    </span>
                    <button
                      type="button"
                      onClick={() => setInvitationToCancel({ id: inv.id, email: inv.email })}
                      className="text-sm text-danger-600 hover:text-danger-700 hover:underline"
                    >
                      {t('team.cancelInvitation')}
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </Card>
      )}

      {invitationToCancel && (
        <Modal
          open
          onClose={() => setInvitationToCancel(null)}
          title={t('team.cancelInvitation')}
          footer={
            <>
              <Button variant="secondary" size="sm" onClick={() => setInvitationToCancel(null)}>
                {t('team.keep')}
              </Button>
              <Button
                variant="danger"
                size="sm"
                onClick={() => {
                  dispatch(cancelTeamInvitation(invitationToCancel.id));
                  setInvitationToCancel(null);
                }}
              >
                {t('team.cancelInvitation')}
              </Button>
            </>
          }
        >
          <div className="space-y-2">
            <p className="text-sm text-surface-700">{t('team.confirmCancelInvitation')}</p>
            <p className="text-sm font-medium text-surface-900">{invitationToCancel.email}</p>
          </div>
        </Modal>
      )}
    </div>
  );
};

export default TeamSettingsPage;
