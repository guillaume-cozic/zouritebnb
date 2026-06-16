import { useEffect, useMemo, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../store/hooks';
import { fetchSolidarityProjects } from './SolidarityProjectSlice';
import {
  selectSolidarityProjects,
  selectSolidarityProjectsStatus,
} from './SolidarityProjectSelectors';
import { SolidarityProject } from './SolidarityProjectTypes';

export interface FeaturedSolidarityProject {
  /** Projet actuellement mis en avant. */
  project: SolidarityProject | null;
  /** Tous les projets parmi lesquels naviguer. */
  projects: SolidarityProject[];
  /** Position du projet courant dans la liste. */
  index: number;
  /** Nombre de projets disponibles. */
  count: number;
  /** Passe au projet suivant / précédent (cyclique). */
  next: () => void;
  prev: () => void;
  loading: boolean;
}

/**
 * Charge les projets solidaires, en tire un au sort comme point de départ, et
 * permet de naviguer dans la liste (suivant / précédent, cyclique).
 */
export const useFeaturedSolidarityProject = (): FeaturedSolidarityProject => {
  const dispatch = useAppDispatch();
  const projects = useAppSelector(selectSolidarityProjects);
  const status = useAppSelector(selectSolidarityProjectsStatus);
  const [seed] = useState(() => Math.random());
  const [offset, setOffset] = useState(0);

  useEffect(() => {
    if (status === 'idle') {
      dispatch(fetchSolidarityProjects());
    }
  }, [dispatch, status]);

  const pool = useMemo(() => {
    const active = projects.filter((p) => p.status === 'active');
    return active.length > 0 ? active : projects;
  }, [projects]);

  const start = pool.length > 0 ? Math.floor(seed * pool.length) % pool.length : 0;
  const index = pool.length > 0 ? (((start + offset) % pool.length) + pool.length) % pool.length : 0;
  const project = pool.length > 0 ? pool[index] : null;

  return {
    project,
    projects: pool,
    index,
    count: pool.length,
    next: () => setOffset((o) => o + 1),
    prev: () => setOffset((o) => o - 1),
    loading: status === 'loading' || status === 'idle',
  };
};
