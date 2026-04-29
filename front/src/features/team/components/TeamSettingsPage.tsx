import React, { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { updateTeamFavoriteProject, inviteCoHost, cancelTeamInvitation, teamSettingsPageOpened } from '../TeamSlice';
import {
  selectCurrentTeam,
  selectTeamError,
  selectTeamStatus,
  selectTeamInvitations,
  selectInviteStatus,
  selectInviteError,
} from '../TeamSelectors';
import { selectSolidarityProjects } from '../../solidarityProject/SolidarityProjectSelectors';
import { selectAuthTeamId, selectAuthUser } from '../../auth/AuthSelectors';
import { updateUserProfile } from '../../auth/AuthSlice';

type SaveState = 'idle' | 'saving' | 'saved' | 'error';

const TeamSettingsPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const team = useAppSelector(selectCurrentTeam);
  const teamStatus = useAppSelector(selectTeamStatus);
  const teamError = useAppSelector(selectTeamError);
  const projects = useAppSelector(selectSolidarityProjects);
  const teamId = useAppSelector(selectAuthTeamId);
  const user = useAppSelector(selectAuthUser);
  const invitations = useAppSelector(selectTeamInvitations);
  const inviteStatus = useAppSelector(selectInviteStatus);
  const inviteError = useAppSelector(selectInviteError);

  const [inviteEmail, setInviteEmail] = useState('');
  const [invitationToCancel, setInvitationToCancel] = useState<{ id: string; email: string } | null>(null);

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [profileState, setProfileState] = useState<SaveState>('idle');
  const [favoriteState, setFavoriteState] = useState<SaveState>('idle');
  const profileTimer = useRef<number | null>(null);
  const isHydrating = useRef(true);

  useEffect(() => {
    if (user) {
      isHydrating.current = true;
      setFirstName(user.firstName ?? '');
      setLastName(user.lastName ?? '');
      setEmail(user.email);
      const handle = window.setTimeout(() => { isHydrating.current = false; }, 0);
      return () => window.clearTimeout(handle);
    }
  }, [user]);

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

  // Autosave profil (debounce 800ms)
  useEffect(() => {
    if (!user || isHydrating.current) return;
    if (profileTimer.current) window.clearTimeout(profileTimer.current);
    profileTimer.current = window.setTimeout(async () => {
      if (!email) return;
      setProfileState('saving');
      const result = await dispatch(updateUserProfile({
        id: user.id,
        firstName: firstName || null,
        lastName: lastName || null,
        email,
      }));
      if (updateUserProfile.fulfilled.match(result)) {
        setProfileState('saved');
        window.setTimeout(() => setProfileState('idle'), 1500);
      } else {
        setProfileState('error');
      }
    }, 800);
    return () => {
      if (profileTimer.current) window.clearTimeout(profileTimer.current);
    };
  }, [firstName, lastName, email, user, dispatch]);

  const activeProjects = projects.filter((p) => p.status === 'active');

  const handleFavoriteChange = async (projectId: string) => {
    if (!team) return;
    setFavoriteState('saving');
    const result = await dispatch(updateTeamFavoriteProject({
      id: team.id,
      favoriteSolidarityProjectId: projectId || null,
    }));
    if (updateTeamFavoriteProject.fulfilled.match(result)) {
      setFavoriteState('saved');
      window.setTimeout(() => setFavoriteState('idle'), 1500);
    } else {
      setFavoriteState('error');
    }
  };

  const SaveIndicator: React.FC<{ state: SaveState }> = ({ state }) => {
    if (state === 'idle') return null;
    if (state === 'saving') return <span className="text-sm text-gray-500">{t('team.autoSaving')}</span>;
    if (state === 'saved') return <span className="text-sm text-green-600">{t('team.saved')}</span>;
    return <span className="text-sm text-red-600">{t('team.saveError')}</span>;
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">{t('team.title')}</h1>
        <p className="text-gray-500 mt-1">{t('team.subtitle')}</p>
      </div>

      {!teamId && (
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
          {t('team.loginRequired')}
        </div>
      )}

      {user && (
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h2 className="text-lg font-semibold text-gray-900">{t('team.profileTitle')}</h2>
              <p className="text-xs text-gray-500">{t('team.profileHelp')}</p>
            </div>
            <SaveIndicator state={profileState} />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-2">{t('team.firstName')}</label>
              <input
                type="text"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">{t('team.lastName')}</label>
              <input
                type="text"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white"
              />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium mb-2">{t('team.email')}</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white"
              />
            </div>
          </div>
        </div>
      )}

      {teamId && teamStatus === 'loading' && !team && (
        <div className="text-center py-12 text-gray-500">{t('homepage.loading')}</div>
      )}
      {teamStatus === 'failed' && (
        <div className="text-center py-12 text-red-500">{teamError}</div>
      )}

      {team && (
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <div className="flex items-start justify-between mb-3">
            <div>
              <label className="block text-sm font-medium">{t('team.favoriteProjectLabel')}</label>
              <p className="text-xs text-gray-500">{t('team.favoriteProjectHelp')}</p>
            </div>
            <SaveIndicator state={favoriteState} />
          </div>
          <select
            value={team.favoriteSolidarityProjectId ?? ''}
            onChange={(e) => handleFavoriteChange(e.target.value)}
            className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
          >
            <option value="">{t('team.favoriteProjectNone')}</option>
            {activeProjects.map((p) => (
              <option key={p.id} value={p.id}>{p.title}</option>
            ))}
          </select>
        </div>
      )}

      {team && (
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mt-6">
          <div className="mb-4">
            <h2 className="text-lg font-semibold text-gray-900">{t('team.coHostsTitle')}</h2>
            <p className="text-xs text-gray-500">{t('team.coHostsHelp')}</p>
          </div>

          <form onSubmit={handleInvite} className="flex flex-col sm:flex-row gap-2 mb-4">
            <input
              type="email"
              required
              placeholder={t('team.inviteEmailPlaceholder')}
              value={inviteEmail}
              onChange={(e) => setInviteEmail(e.target.value)}
              className="flex-1 h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white"
            />
            <button
              type="submit"
              disabled={inviteStatus === 'loading' || !inviteEmail.trim()}
              className="h-11 px-5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-60"
            >
              {inviteStatus === 'loading' ? t('team.inviting') : t('team.inviteButton')}
            </button>
          </form>

          {inviteStatus === 'succeeded' && (
            <div className="mb-4 text-sm text-green-600">{t('team.invitationSent')}</div>
          )}
          {inviteStatus === 'failed' && inviteError && (
            <div className="mb-4 text-sm text-red-600">{inviteError}</div>
          )}

          <h3 className="text-sm font-medium text-gray-700 mb-2">{t('team.pendingInvitations')}</h3>
          {invitations.length === 0 ? (
            <p className="text-sm text-gray-400">{t('team.noPendingInvitations')}</p>
          ) : (
            <ul className="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
              {invitations.map((inv) => (
                <li key={inv.id} className="flex items-center justify-between px-4 py-3 bg-gray-50/50">
                  <div className="flex flex-col">
                    <span className="text-sm text-gray-900">{inv.email}</span>
                    <span className="text-xs text-gray-400">
                      {new Date(inv.createdAt).toLocaleDateString()}
                    </span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                      {t('team.invitationPending')}
                    </span>
                    <button
                      type="button"
                      onClick={() => setInvitationToCancel({ id: inv.id, email: inv.email })}
                      className="text-sm text-red-600 hover:text-red-700 hover:underline"
                    >
                      {t('team.cancelInvitation')}
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      {invitationToCancel && (
        <div
          className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4"
          onClick={() => setInvitationToCancel(null)}
        >
          <div
            className="bg-white rounded-xl shadow-xl w-full max-w-md"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="px-6 py-4 border-b border-gray-100">
              <h2 className="text-lg font-semibold text-gray-900">{t('team.cancelInvitation')}</h2>
            </div>
            <div className="px-6 py-5 space-y-2">
              <p className="text-sm text-gray-700">{t('team.confirmCancelInvitation')}</p>
              <p className="text-sm font-medium text-gray-900">{invitationToCancel.email}</p>
            </div>
            <div className="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
              <button
                type="button"
                onClick={() => setInvitationToCancel(null)}
                className="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
              >
                {t('team.keep')}
              </button>
              <button
                type="button"
                onClick={() => {
                  dispatch(cancelTeamInvitation(invitationToCancel.id));
                  setInvitationToCancel(null);
                }}
                className="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700"
              >
                {t('team.cancelInvitation')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default TeamSettingsPage;
