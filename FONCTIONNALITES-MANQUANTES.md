# Fonctionnalités manquantes — site type Airbnb

> État des lieux au 2026-06-26.

Le site couvre déjà solidement le cœur d'un Airbnb : recherche avec filtres + carte,
fiches logements, calendrier de disponibilité, réservation avec demande/confirmation,
paiement Stripe, annulation avec politique de remboursement, avis croisés, messagerie,
wishlist, équipes/co-hôtes, vérification d'identité, projets solidaires.

Voici ce qui **manque** pour se rapprocher d'un Airbnb complet, classé par priorité.

## ✅ Corrigé

- **Recherche par dates** : le catalogue filtre désormais sur les disponibilités. Les
  paramètres `checkIn`/`checkOut` (déjà envoyés par le front) excluent les logements ayant
  une réservation `pending`/`confirmed` qui chevauche la période (comparaison au jour, le
  turnover même jour reste disponible).
- **Tri des résultats** : le catalogue se trie via le paramètre `sort` (Recommandé par
  défaut, prix croissant/décroissant, mieux notés). Sélecteur dans la barre de résultats.
- **Réservation instantanée** : toggle par logement (côté hôte). Quand il est actif, les
  demandes des voyageurs sont confirmées automatiquement et le paiement capturé, sans
  validation de l'hôte. Badge + CTA dédiés sur la page détail, filtre de recherche
  `instantBooking` + badge sur les cartes. Seeder `app:seed:instant-booking` pour tester.
- **Blocage de dates par l'hôte** : depuis le calendrier du backoffice, l'hôte sélectionne
  une plage pour la bloquer (vacances, maintenance, indispo externe). Motif optionnel,
  libellé « Bloqué » par défaut. Les dates bloquées rendent le logement indisponible à la
  réservation comme une réservation classique.

## 🔴 Manques importants (impact direct conversion/usage)

- **Recherche full-text** : pas de recherche par mot-clé (titre/description). Uniquement
  ville + capacité + prix + équipements.
- **Remboursement réel au voyageur** : l'annulation calcule le montant et annule le
  PaymentIntent Stripe, mais **aucun vrai remboursement carte** n'est émis. Le breakdown
  existe mais l'argent n'est pas rendu.

## 🟠 Manques structurants

- **Tarification dynamique** : prix fixe par nuit + promo hebdo uniquement. Pas de tarifs
  saisonniers, prix par date, week-end, last-minute.
- **Codes promo / coupons** : aucun.
- **Type de logement** : pas de catégorie (appartement, villa, chambre…), pas de filtre par type.
- **Séjour min/max** : aucune contrainte de durée minimale.
- **Modification de réservation** : impossible de changer dates/voyageurs après création.
- **Réponses de l'hôte aux avis** : les avis sont à sens unique, l'hôte ne peut pas répliquer.
- **Annulation par l'hôte** : seul le voyageur peut annuler ; pas de flux d'annulation hôte
  (avec pénalité/compensation).
- **Multi-langue front** : le back gère FR/EN mais le front est FR uniquement.

## 🟡 Manques « expérience » / confiance

- **Notifications in-app + push** : tout est par email (et SMS pour la demande). Pas de centre
  de notifications ni de push.
- **Login social** (Google/Apple) : absent.
- **Pièces jointes / photos dans la messagerie** : texte seul.
- **Modération des avis / signalement** : pas de système de flag.
- **Règlement intérieur & règles maison** : pas de house rules sur la fiche.
- **Politiques d'annulation** : seulement flexible / modérée — pas de « stricte » ni personnalisable.
- **Devise** : EUR codé en dur, pas de multi-devise.

## ⚪ Plus loin (croissance / pro)

- Carte avec recherche par rayon géographique / « rechercher dans cette zone ».
- Logements multi-unités / gestion par chambre.
- Programme de fidélité, parrainage.
- Tableau de bord revenus/payouts pour l'hôte (l'IBAN est stocké mais pas de flux de versement visible).
- Acceptation des CGU à la réservation, génération de reçus côté voyageur.

---

## Top 3 prioritaire

1. ~~**Recherche par dates de disponibilité**~~ ✅ fait
2. **Remboursement réel** au voyageur lors d'une annulation
3. **Blocage manuel de dates par l'hôte**

Ce sont les trois trous les plus visibles entre l'expérience actuelle et un vrai Airbnb.
Tous faisables dans l'architecture hexagonale existante.
