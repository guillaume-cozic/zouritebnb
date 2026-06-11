import React from 'react';
import { Link } from 'react-router-dom';
import { SolidarityProject } from '../SolidarityProjectTypes';

interface Props {
  project: SolidarityProject;
}

const SolidarityProjectCard: React.FC<Props> = ({ project }) => {
  return (
    <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden flex flex-col group hover:shadow-lg transition-all">
      <Link
        to={`/solidarity-projects/${project.id}`}
        className="aspect-[16/9] relative overflow-hidden bg-gradient-to-br from-primary-50 to-primary-100 cursor-pointer block"
      >
        {project.imageUrl ? (
          <img
            src={project.imageUrl}
            alt={project.title}
            className="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
          />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="text-primary-300">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
        )}
      </Link>
      <div className="p-6 flex flex-col flex-1">
        <Link
          to={`/solidarity-projects/${project.id}`}
          className="font-semibold tracking-tight text-xl mb-3 hover:text-primary-600 transition-colors"
        >
          {project.title}
        </Link>
        <p className="text-gray-500 text-sm leading-relaxed whitespace-pre-line">
          {project.description}
        </p>
      </div>
    </div>
  );
};

export default SolidarityProjectCard;
