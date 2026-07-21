import React, { useState } from 'react';

interface LiteYouTubeProps {
  videoId: string;
  title: string;
  className?: string;
}

/**
 * Façade YouTube : affiche la miniature + un bouton play, et ne charge le
 * player (youtube-nocookie, sans cookie tiers) qu'au clic. Évite ~500 Ko de
 * JS YouTube et le cookie tiers au chargement de la page.
 */
const LiteYouTube: React.FC<LiteYouTubeProps> = ({ videoId, title, className = '' }) => {
  const [activated, setActivated] = useState(false);

  if (activated) {
    return (
      <iframe
        src={`https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1`}
        title={title}
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        allowFullScreen
        className={className}
      />
    );
  }

  return (
    <button
      type="button"
      onClick={() => setActivated(true)}
      aria-label={title}
      className={`group/yt cursor-pointer border-0 p-0 bg-black ${className}`}
    >
      <img
        src={`https://i.ytimg.com/vi/${videoId}/sddefault.jpg`}
        alt={title}
        loading="lazy"
        className="absolute inset-0 h-full w-full object-cover opacity-90 group-hover/yt:opacity-100 transition-opacity"
      />
      <span className="absolute inset-0 flex items-center justify-center">
        <span className="flex items-center justify-center w-16 h-12 rounded-xl bg-black/70 group-hover/yt:bg-red-600 transition-colors">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
            <path d="M8 5v14l11-7z" />
          </svg>
        </span>
      </span>
    </button>
  );
};

export default LiteYouTube;
