import React, { useMemo } from 'react';
import { useAppSelector } from '../../../store/hooks';
import RodriguesMap, { CATEGORY_META } from '../../homepage/components/RodriguesMap';
import { selectActivityPoints } from '../ActivityPointSelectors';
import type { ActivityPoint, ActivityPointCategory } from '../ActivityPointTypes';

/**
 * Dedicated /activites page: the Rodrigues activities map as the main content
 * (H1), followed by the full list of spots grouped by category — crawlable
 * content with the optional article links, complementing the map popups.
 */
const ActivitiesMapPage: React.FC = () => {
  const points = useAppSelector(selectActivityPoints);

  const groups = useMemo(() => {
    const byCategory = new Map<ActivityPointCategory, ActivityPoint[]>();
    points.forEach((point) => {
      if (!(point.category in CATEGORY_META)) return;
      const group = byCategory.get(point.category) ?? [];
      group.push(point);
      byCategory.set(point.category, group);
    });
    return (Object.keys(CATEGORY_META) as ActivityPointCategory[])
      .filter((category) => byCategory.has(category))
      .map((category) => ({
        category,
        points: [...(byCategory.get(category) ?? [])].sort((a, b) =>
          a.name.localeCompare(b.name, 'fr'),
        ),
      }));
  }, [points]);

  return (
    <div className="bg-white">
      {/* The map section fetches the points itself and renders the H1. */}
      <RodriguesMap headingLevel="h1" />

      {groups.length > 0 && (
        <section className="pb-16">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 className="text-2xl font-bold text-gray-900 text-center mb-10">
              Tous les spots de l'île
            </h2>

            <div className="space-y-12">
              {groups.map(({ category, points: categoryPoints }) => {
                const meta = CATEGORY_META[category];
                return (
                  <div key={category}>
                    <h3 className="flex items-center gap-2 text-xl font-semibold text-gray-900 mb-4">
                      <span
                        className="flex h-8 w-8 items-center justify-center rounded-full text-base"
                        style={{ background: meta.color }}
                      >
                        {meta.emoji}
                      </span>
                      {meta.label}
                    </h3>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                      {categoryPoints.map((point) => (
                        <article
                          key={point.id}
                          className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
                        >
                          <h4 className="font-semibold text-gray-900">{point.name}</h4>
                          <p className="mt-1 text-sm text-gray-600">{point.description}</p>
                          {point.articleUrl && (
                            <a
                              href={point.articleUrl}
                              target="_blank"
                              rel="noreferrer"
                              className="mt-2 inline-block text-sm font-medium text-primary-600 hover:text-primary-700"
                            >
                              Lire l'article →
                            </a>
                          )}
                        </article>
                      ))}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </section>
      )}
    </div>
  );
};

export default ActivitiesMapPage;
