# Fonctionnalités manquantes — site type Airbnb

> État des lieux au 2026-06-26.

Le site couvre déjà solidement le cœur d'un Airbnb : recherche avec filtres + carte,
fiches logements, calendrier de disponibilité, réservation avec demande/confirmation,
paiement Stripe, annulation avec politique de remboursement, avis croisés, messagerie,
wishlist, équipes/co-hôtes, vérification d'identité, projets solidaires.

Voici ce qui **manque** pour se rapprocher d'un Airbnb complet, classé par priorité.

## ✅ Corrigé

- **Recherche full-text** : recherche par mots-clés sur le titre et la description via le
  paramètre `q` (tous les mots doivent correspondre, insensible à la casse). Champ
  « Mots-clés » en tête de la barre de recherche du catalogue, avec chip de filtre actif.
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
- **Type de logement** : catégorie par logement (appartement, maison, villa, studio, chambre,
  bungalow) éditable par l'hôte, affichée sur la fiche/les cartes, + filtre de recherche `type`.
- **Séjour min/max** : l'hôte définit un nombre de nuits min et/ou max par logement ; une
  demande de réservation hors bornes est refusée (422). Affiché sur la fiche.
- **Annulation par l'hôte** : l'hôte peut annuler une réservation confirmée depuis la
  messagerie. Le voyageur est alors **intégralement remboursé** (compensation, quelle que
  soit la politique). Un message d'explication au voyageur est **obligatoire**.
- **Blocage de dates par l'hôte** : depuis le calendrier du backoffice, l'hôte sélectionne
  une plage pour la bloquer (vacances, maintenance, indispo externe). Motif optionnel,
  libellé « Bloqué » par défaut. Les dates bloquées rendent le logement indisponible à la
  réservation comme une réservation classique.
- **Tarification dynamique** : en plus du prix fixe et de la promo hebdo, l'hôte définit une
  majoration week-end (vendredi/samedi), une remise last-minute (X % si réservé à moins de
  N jours de l'arrivée) et des tarifs par période (saisonnier / dates). Le calcul par nuit
  est centralisé (`StayPriceCalculator`), figé à la réservation, affiché côté voyageur et
  appliqué au paiement. Endpoints `PATCH /accommodations/{id}/dynamic-pricing` et
  `PUT /accommodations/{id}/price-periods`.
- **Modification de réservation** : depuis la messagerie, le voyageur propose de nouvelles
  dates sur une réservation confirmée non commencée ; le prix est recalculé et figé dans une
  modification en attente, que l'hôte accepte ou refuse (les nouvelles dates/prix s'appliquent
  à l'acceptation). Disponibilité revérifiée (hors elle-même) à la demande et à l'acceptation.
  Pas de charge/remboursement Stripe de l'écart (cohérent avec le manque « remboursement réel »).

- **Multi-langue** : le front avait déjà le sélecteur FR/EN (i18next + `Accept-Language`
  sur les appels projets solidaires) ; le contenu est maintenant traduit — les 14 articles
  du blog existent en anglais sous `/blog/en/` et les projets solidaires ont leur
  traduction `en` en base, servie selon l'en-tête `Accept-Language`.

## 🔴 Manques importants (impact direct conversion/usage)

- **Remboursement réel au voyageur** : l'annulation calcule le montant et annule le
  PaymentIntent Stripe, mais **aucun vrai remboursement carte** n'est émis. Le breakdown
  existe mais l'argent n'est pas rendu.

## 🟠 Manques structurants

- **Codes promo / coupons** : aucun.
- **Réponses de l'hôte aux avis** : les avis sont à sens unique, l'hôte ne peut pas répliquer.

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
