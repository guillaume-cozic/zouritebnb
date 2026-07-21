<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Port;

interface SolidarityProjectImageTransformer
{
    /**
     * Recompresse l'image en WebP avec une largeur plafonnée à la taille
     * d'affichage du hero, et retourne les octets résultants. L'image du
     * projet mis en avant est le LCP de la page d'accueil : servir un
     * original plein format plombe directement le temps de chargement.
     */
    public function toHeroWebp(string $content): string;
}
