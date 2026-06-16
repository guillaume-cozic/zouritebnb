# Documentation fonctionnelle — Plateforme BnB Rodrigues

> Documentation produit décrivant **ce que fait la plateforme** d'un point de vue
> métier et utilisateur. Elle ne décrit pas l'implémentation technique.

## 1. Présentation générale

**BnB** est une place de marché de location de vacances dédiée à l'île de **Rodrigues**.
Elle met en relation des **voyageurs** et des **hôtes** (loueurs), avec une particularité :
**chaque séjour réservé soutient un projet solidaire local**.

La plateforme se compose de trois applications :

| Application | Public | Rôle |
|-------------|--------|------|
| **Front** | Voyageurs + Hôtes | Site public (recherche, réservation) et back-office hôte |
| **Admin** | Administrateurs plateforme | Supervision de l'activité et gestion des projets solidaires |
| **Blog** | Public | Contenu éditorial sur Rodrigues (activités, découverte) |

### Rôles utilisateurs

- **Voyageur** — recherche, réserve, échange avec l'hôte, laisse des avis, vérifie son identité.
- **Hôte / loueur** — publie des hébergements, gère ses réservations et son calendrier,
  échange avec les voyageurs, invite des co-hôtes, configure ses versements.
- **Administrateur** — supervise l'ensemble de l'activité et administre les projets solidaires.

Un même compte peut être **voyageur et hôte** : un sélecteur permet de basculer entre
le « mode hôte » et le « mode voyageur ».

---

## 2. Compte et identité

### Inscription / Connexion
- Création de compte par **email + mot de passe** (8 caractères minimum).
- Connexion par email / mot de passe, renvoyant un jeton de session.
- À l'inscription, une **équipe personnelle** est automatiquement créée pour l'utilisateur
  (toute personne appartient à une équipe — voir §8).
- Les pages de connexion et d'inscription **mettent en avant un projet solidaire tiré au sort**.
- Lorsqu'un utilisateur non connecté tente de réserver, il est redirigé vers la connexion /
  inscription avec un message rassurant : *aucun montant n'est prélevé tant que l'hôte n'a pas accepté*.

### Vérification d'identité (KYC)
Pour renforcer la confiance entre hôtes et voyageurs :
- Sélection d'un **type de document** : passeport, carte d'identité ou permis de conduire.
- Téléversement d'une **photo du document** et d'un **selfie**.
- Suivi de l'avancement (upload puis phase d'analyse).
- Statuts possibles : *non démarrée*, *en attente*, *vérifiée*, *rejetée*.
- Le statut de vérification est visible sur le profil.

---

## 3. Hébergements

### Côté voyageur — découverte
- **Page d'accueil** : moteur de recherche (lieu, dates d'arrivée/départ, nombre de voyageurs),
  hébergements mis en avant, présentation de l'île, activités, projets solidaires.
- **Listing** (`/accommodations`) : grille filtrable d'hébergements publiés avec carte,
  filtres avancés (fourchette de prix, équipements).
- **Page détail** d'un hébergement :
  - Galerie photos, description, capacité, équipements, localisation sur carte.
  - **Calcul du prix** : tarif à la nuit, remise hebdomadaire (séjours de 7 nuits et +),
    commission plateforme et **contribution solidaire**.
  - Avis voyageurs et note moyenne.
  - Sélecteur de dates et bouton de réservation.
  - **Projet solidaire associé** (projet coup de cœur de l'hôte ou projet par défaut).
  - La **localisation exacte** n'est pas révélée aux voyageurs non confirmés (rayon approximatif
    affiché pour préserver la vie privée de l'hôte).

### Côté hôte — création et gestion
- **Assistant de création** en plusieurs étapes :
  1. **Description** — titre, description, prix/nuit, remise hebdomadaire.
  2. **Capacité** — chambres, salles de bain, voyageurs max, nombre de lits.
  3. **Équipements** — sélection dans une taxonomie catégorisée (cuisine, salle de bain,
     chambre, extérieur, loisirs, enfants, accessibilité, arrivée, environnement…).
  4. **Localisation** — adresse, ville, code postal, pays, positionnement carte optionnel.
  5. **Photos** — jusqu'à 20 images (JPEG/PNG/WebP), réordonnables.
  6. **Confirmation** — récapitulatif, création en **brouillon**.
- **Édition** d'un hébergement existant et **gestion des photos** (ajout, suppression, réordonnancement).
- **Publication / dépublication** : un hébergement est soit en **brouillon** (non visible),
  soit **publié** (visible et réservable).

### Règles métier
- Le prix à la nuit doit être strictement positif.
- La remise hebdomadaire (pourcentage entre 0 et 100) ne s'applique qu'aux séjours de 7 nuits et plus.
- Seuls les membres de l'équipe propriétaire peuvent modifier / publier un hébergement.

---

## 4. Réservation

### Cycle de vie d'une réservation

```
Parcours public (voyageur) :
  Demande → [En attente] ──(hôte accepte)──→ [Confirmée]   + paiement capturé
                         └─(hôte refuse)───→ [Refusée]      + pré-autorisation libérée
                         └─(24 h sans réponse)→ [Refusée]   (refus automatique)
  En attente / Confirmée ──(annulation)──→ [Annulée]

Parcours back-office (hôte) :
  Création directe → [Confirmée]   (sans paiement, ex. réservation par téléphone)
```

Statuts : **en attente**, **confirmée**, **refusée**, **annulée** (et **expirée** côté supervision).

### Côté voyageur
1. **Demande de réservation** depuis la page détail : dates, nombre de voyageurs, message
   optionnel à l'hôte. Une **pré-autorisation de paiement** est posée (aucun débit immédiat).
2. **Confirmation de réservation** (`/accommodations/:id/book`) : récapitulatif, saisie du moyen
   de paiement (Stripe), détail du prix (nuits, remise, commission, contribution solidaire),
   choix du projet solidaire à soutenir.
3. **Page de succès** : message de confirmation avec délai de réponse de l'hôte (24 h),
   accès aux conversations.

### Côté hôte
- **Liste des réservations** (`/admin/reservations`) filtrable par statut, avec compteur des demandes en attente.
- **Acceptation / refus** depuis la conversation : panneau latéral avec les informations du
  voyageur, les dates et le prix total ; **24 h pour décider**.
  - Acceptation → **paiement capturé**, réservation confirmée.
  - Refus → **pré-autorisation libérée**, réservation refusée.
- **Calendriers** : vue de tous les hébergements ou par hébergement, création de réservations manuelles.

### Règles métier
- La date de départ doit être strictement postérieure à la date d'arrivée.
- Le prix est **calculé côté serveur** (jamais d'après une valeur fournie par le client).
- Aucun débit pour les réservations refusées ou annulées.
- Seuls les séjours **confirmés et terminés** (date de départ passée) ouvrent droit aux avis.

---

## 5. Paiement

Paiement intégré via **Stripe**, en mode **pré-autorisation puis capture** :
- Création d'un *payment intent* à la demande de réservation ; le montant est **calculé côté serveur**
  (devise EUR), jamais transmis par le client.
- Les données de carte sont gérées par Stripe (le serveur applicatif ne les voit pas).
- **Capture** du paiement uniquement lorsque l'hôte **accepte** la réservation.
- **Libération** de la pré-autorisation si l'hôte refuse ou si le voyageur annule avant confirmation.
- Le montant total couvre : prix de l'hébergement + commission plateforme + contribution solidaire.
- États de paiement : *en attente*, *autorisé*, *capturé*, *annulé*, *échoué*.
- Les **webhooks Stripe** mettent à jour le statut de paiement de façon idempotente.

> **Note** : les réservations créées en back-office par l'hôte ne déclenchent pas de paiement.

---

## 6. Messagerie

Canal d'échange entre voyageur et hôte, lié à une demande de réservation.
- Une **conversation est ouverte automatiquement** quand un voyageur envoie une demande de réservation.
- Les deux parties peuvent échanger des **messages** ; des **messages système** marquent les
  changements d'état (ex. « réservation confirmée »).
- **Côté voyageur** (`/account/conversations`) : boîte de réception de ses demandes.
- **Côté hôte** (`/admin/conversations`) : conversations filtrées par hébergement, recherche par
  voyageur / hébergement, filtre « à traiter », et actions d'acceptation / refus directement dans le fil.
- Seuls les participants (voyageur et membres de l'équipe hôte) accèdent à une conversation.

---

## 7. Avis

Système d'évaluation **bidirectionnel** après un séjour terminé.
- **Avis sur l'hébergement** (rédigé par le voyageur) : note de 1 à 5 étoiles + commentaire
  (50 caractères minimum). **Affiché publiquement** sur la page de l'hébergement et agrégé en note moyenne.
- **Avis sur le voyageur** (rédigé par l'hôte) : note + commentaire ; **non public**,
  sert à la confiance entre hôtes.

### Règles métier
- Un seul avis par séjour et par sens (voyageur→hébergement, hôte→voyageur).
- Avis possible uniquement pour une réservation **confirmée** et **après la date de départ**.
- Le nom du voyageur est affiché de façon abrégée sur les avis publics.

---

## 8. Équipe et co-hôtes

Chaque utilisateur dispose d'une **équipe** (créée à l'inscription) qui **possède** les hébergements
et les réservations. Une équipe peut regrouper plusieurs co-hôtes.

Depuis les paramètres (`/admin/team`) :
- **Profil** : prénom, nom, email (enregistrement automatique).
- **Projet solidaire coup de cœur** : projet mis en avant sur tous les hébergements de l'équipe
  et pré-sélectionné dans le formulaire de réservation.
- **Invitation de co-hôtes** par email, avec liste des invitations en attente et possibilité d'annuler.
- **Compte bancaire** (IBAN + BIC optionnel + titulaire) pour recevoir les **versements** des réservations.
  Le titulaire est requis dès qu'un IBAN est renseigné.

---

## 9. Projets solidaires

Élément différenciant de la plateforme : **chaque réservation soutient un projet solidaire local**.

### Côté public
- **Mise en avant** sur la page d'accueil (carrousel avec navigation), sur les pages de connexion /
  inscription (projet tiré au sort) et sur les pages des hébergements.
- **Annuaire** (`/solidarity-projects`) : tous les projets actifs et clôturés, avec image, titre,
  extrait et statut (*en cours* / *clôturé*).
- **Page détail** : description complète, **chiffres clés**, temps de lecture, boutons de partage.
- Lors de la réservation, le voyageur **choisit le projet** qu'il souhaite soutenir ; une part du
  prix (contribution solidaire) lui est allouée.

### Administration (app Admin)
- **Création / édition** d'un projet : titre, description (HTML), image, chiffres clés (valeur + libellé).
- **Statut** actif / clôturé (les projets clôturés ne sont plus affichés au public).
- **Projet par défaut** : projet de repli mis en avant lorsqu'aucun projet n'est choisi par l'hôte ou le voyageur.

---

## 10. Back-office administrateur (app Admin)

Tableau de bord de **supervision** réservé aux administrateurs (`ROLE_ADMIN`), majoritairement en
**lecture seule**, avec gestion complète des projets solidaires.

| Section | Contenu |
|---------|---------|
| **Tableau de bord** | Chiffre d'affaires total, marge plateforme (taux de commission), montant reversé aux projets (taux), dons par projet, compteurs (réservations, hébergements, avis, clients, séjours à venir) |
| **Réservations** | Liste paginée, recherche (voyageur / hébergement), filtre par statut |
| **Hébergements** | Liste paginée (brouillons inclus), recherche (titre / ville / email hôte), filtre par statut |
| **Avis** | Liste des avis, recherche, filtre par type (hébergement / voyageur) |
| **Clients** | Liste des utilisateurs, recherche, filtre par rôle, statut de vérification, nombre d'hébergements / réservations |
| **Projets solidaires** | **Seule section pleinement éditable** : création, édition, activation/désactivation, projet par défaut |

Les indicateurs du tableau de bord sont cliquables et renvoient vers la section correspondante.

---

## 11. Données de référence — Géographie

Référentiel de localisation utilisé pour le filtrage et le placement des hébergements :
- **Régions** et **localités** de Rodrigues, consultables publiquement, utilisées par la recherche
  (suggestions de lieux) et l'adressage des hébergements.

---

## 12. Synthèse des parcours clés

### Réservation publique (voyageur → hôte)
1. Le voyageur recherche et consulte un hébergement publié.
2. Il choisit ses dates, saisit son moyen de paiement (pré-autorisation) et le projet à soutenir.
3. Il envoie sa **demande de réservation** → une conversation s'ouvre automatiquement.
4. L'hôte dispose de **24 h** pour **accepter** (paiement capturé) ou **refuser** (pré-autorisation libérée) ;
   sans réponse, la demande est refusée automatiquement.
5. Après le séjour, voyageur et hôte peuvent échanger des **avis**.

### Mise en ligne d'un hébergement (hôte)
1. Création en **brouillon** via l'assistant (description, capacité, équipements, localisation, photos).
2. **Publication** pour rendre l'hébergement visible et réservable.
3. Ajustements possibles à tout moment (prix, remise, photos, etc.).

### Collaboration en équipe (hôtes)
1. Inscription → création d'une équipe personnelle.
2. **Invitation de co-hôtes** par email.
3. Configuration du **compte bancaire** (versements) et du **projet solidaire** mis en avant.

---

## Lexique

| Terme | Définition |
|-------|------------|
| **Voyageur** | Utilisateur qui réserve un hébergement |
| **Hôte / loueur** | Propriétaire d'un ou plusieurs hébergements |
| **Hébergement** | Annonce / logement publié sur la plateforme |
| **Équipe** | Groupe propriétaire des hébergements (un ou plusieurs co-hôtes) |
| **Demande de réservation** | Première étape d'une réservation, en attente de l'accord de l'hôte |
| **Pré-autorisation** | Blocage du paiement sans débit, capturé seulement à l'acceptation |
| **Projet solidaire** | Initiative locale soutenue par une part de chaque réservation |
| **Contribution solidaire** | Part du prix reversée au projet solidaire |
| **Back-office** | Espace de gestion de l'hôte |
| **Vérification d'identité** | Contrôle KYC (document + selfie) renforçant la confiance |
