# Emails transactionnels — Parcours voyageur

> Contenu (copywriting) des emails envoyés au **voyageur** aux différentes étapes de son
> parcours. Document de référence pour l'intégration ultérieure d'un système d'emails.
> Langue : français. Marque : **BnB Rodrigues**.

## Conventions

- **Expéditeur** : `BnB Rodrigues <bonjour@bnb-rodrigues.com>`
- **Ton** : chaleureux, tutoiement évité (vouvoiement), proche et rassurant, avec une touche
  « îles » et la dimension solidaire propre à la plateforme.
- **Signature** : *L'équipe BnB Rodrigues 🌴*
- **Pied de page commun** (tous les emails) :
  > Vous recevez cet email car vous avez un compte sur BnB Rodrigues.
  > [Gérer mes notifications]({{lien_preferences}}) · [Aide]({{lien_aide}}) · BnB Rodrigues
- **Variables** (à remplacer à l'envoi) :

| Variable | Description |
|----------|-------------|
| `{{prenom}}` | Prénom du voyageur |
| `{{hebergement}}` | Titre de l'hébergement |
| `{{ville}}` | Ville / localité de l'hébergement |
| `{{hote}}` | Prénom de l'hôte |
| `{{date_arrivee}}` / `{{date_depart}}` | Dates du séjour |
| `{{nb_nuits}}` | Nombre de nuits |
| `{{nb_voyageurs}}` | Nombre de voyageurs |
| `{{montant_total}}` | Montant total TTC |
| `{{contribution_solidaire}}` | Part reversée au projet solidaire |
| `{{projet_solidaire}}` | Nom du projet solidaire soutenu |
| `{{lien_*}}` | URL de l'action correspondante |

### Vue d'ensemble des emails

| # | Déclencheur (événement) | Email |
|---|--------------------------|-------|
| 1 | Inscription (`UserRegistered`) | Bienvenue |
| 2 | Identité vérifiée (`IdentityVerified`) | Identité confirmée |
| 3 | Demande envoyée (`ReservationRequested`) | Demande de réservation reçue |
| 4 | Réservation confirmée (`ReservationConfirmed` / `PaymentCaptured`) | Réservation confirmée 🎉 |
| 5 | Réservation refusée (`ReservationRefused`) | Demande non retenue |
| 6 | Expiration automatique (24 h) | Demande expirée |
| 7 | Annulation (`ReservationCancelled`) | Réservation annulée |
| 8 | Nouveau message (`MessagePosted`, de l'hôte) | Nouveau message de votre hôte ✅ branché |
| 9 | Avant l'arrivée (J-3) | Votre séjour approche |
| 10 | Après le départ | Partagez votre avis |

---

## Implémentation — Outbox pattern

L'envoi suit un **transactional outbox pattern**, dans le module `API/src/Notification/` :

1. **Écriture transactionnelle.** Des *listeners* réagissent aux événements de domaine
   (`UserRegistered`, `ReservationRequested`, `ReservationConfirmed`, `ReservationRefused`,
   `ReservationCancelled`) et **persistent l'email dans la table `outbox_email`** (statut
   `pending`) via le cas d'usage `QueueEmail`. L'email à envoyer est donc enregistré comme une
   donnée, dans la même unité de travail que la réaction métier.
   - **Vues HTML** : le corps est une **vue Twig** (`API/templates/emails/traveler/*.html.twig`,
     étendant `emails/base.html.twig`). Le builder `TravelerEmails` ne produit que
     `(template, sujet, variables)` ; le rendu HTML est fait par le port `EmailRenderer`
     (adaptateur `TwigEmailRenderer`) au moment de la mise en file, et le HTML est figé dans
     l'outbox.
2. **Relais d'envoi.** La commande `bin/console app:emails:send-pending` (cas d'usage
   `SendPendingEmails`) lit les emails `pending` et les envoie via le port `EmailSender`
   (adaptateur Symfony Mailer). Chaque email est sauvegardé indépendamment : un échec n'interrompt
   pas le lot.
3. **Idempotence & reprises.** Un échec d'envoi incrémente `attempts` et garde l'email `pending`
   (réessayé au prochain passage) jusqu'à `maxAttempts`, après quoi il passe en `failed`
   (dead-letter). Les envois réussis passent en `sent`.
4. **Renvoi manuel.** `bin/console app:emails:resend <id>` (cas d'usage `ResendEmail`) renvoie un
   email précis quel que soit son statut — typiquement un `failed` resté bloqué après une panne
   réseau. L'issue est enregistrée sur l'outbox (sent / nouvelle tentative échouée).

**Exploitation en production :**
- Faire tourner le worker Messenger qui consomme les événements de domaine
  (`bin/console messenger:consume async`) — c'est lui qui remplit l'outbox.
- Planifier le relais (cron / Messenger Scheduler), par ex. chaque minute :
  `bin/console app:emails:send-pending`.
- Configurer l'expéditeur et le transport via `MAILER_DSN`, `MAILER_FROM_EMAIL`, `MAILER_FROM_NAME`.

> Les emails « rappel J-3 » et « avis après séjour » ci-dessous ne sont pas encore branchés à un
> événement ; leur copie est prête pour quand les déclencheurs existeront. L'email « nouveau
> message » est désormais branché sur `MessagePosted` (voir §8).

---

## 1. Bienvenue (inscription)

**Déclencheur :** `UserRegistered`

- **Objet :** Bienvenue sur BnB Rodrigues, {{prenom}} 🌴
- **Pré-en-tête :** Votre compte est prêt — l'île vous attend.

> Bonjour {{prenom}},
>
> Bienvenue sur **BnB Rodrigues** ! Votre compte est désormais actif.
>
> Ici, vous trouverez des hébergements uniques tenus par des hôtes locaux — et **chaque séjour
> réservé soutient un projet solidaire de l'île**. Voyager rime avec impact positif.
>
> Pour bien démarrer :
> - 🔍 [Découvrir les hébergements]({{lien_recherche}})
> - 💚 [Explorer les projets solidaires]({{lien_projets}})
> - ✅ [Vérifier votre identité]({{lien_verification}}) pour réserver en toute confiance
>
> À très vite sur Rodrigues,
> L'équipe BnB Rodrigues 🌴

---

## 2. Identité confirmée

**Déclencheur :** `IdentityVerified`

- **Objet :** Votre identité est vérifiée ✅
- **Pré-en-tête :** Vous pouvez désormais réserver en toute sérénité.

> Bonjour {{prenom}},
>
> Bonne nouvelle : votre identité a bien été **vérifiée**. Ce badge rassure les hôtes et vous
> permet de réserver plus sereinement.
>
> [Trouver mon prochain séjour]({{lien_recherche}})
>
> L'équipe BnB Rodrigues 🌴

---

## 3. Demande de réservation reçue

**Déclencheur :** `ReservationRequested`

- **Objet :** Votre demande pour « {{hebergement}} » a bien été envoyée
- **Pré-en-tête :** {{hote}} a 24 h pour répondre — aucun montant n'est encore prélevé.

> Bonjour {{prenom}},
>
> Votre **demande de réservation** a bien été transmise à votre hôte. 🙌
>
> **Récapitulatif**
> - 🏠 Hébergement : **{{hebergement}}**, {{ville}}
> - 📅 Du **{{date_arrivee}}** au **{{date_depart}}** ({{nb_nuits}} nuits)
> - 👥 {{nb_voyageurs}} voyageur(s)
> - 💳 Total : **{{montant_total}}** — *autorisé, non débité*
> - 💚 Projet soutenu : {{projet_solidaire}}
>
> **Que se passe-t-il maintenant ?**
> {{hote}} dispose de **24 heures** pour accepter ou refuser votre demande.
> **Aucun montant ne sera prélevé tant que votre hôte n'a pas accepté.** Si la demande n'est pas
> acceptée, l'autorisation de paiement est automatiquement libérée.
>
> En attendant, vous pouvez échanger avec votre hôte :
> [Voir ma conversation]({{lien_conversation}})
>
> L'équipe BnB Rodrigues 🌴

---

## 4. Réservation confirmée 🎉

**Déclencheur :** `ReservationConfirmed` (+ `PaymentCaptured`)

- **Objet :** C'est confirmé ! Votre séjour à {{ville}} est réservé 🎉
- **Pré-en-tête :** {{hote}} a accepté votre demande. Préparez vos valises !

> Bonjour {{prenom}},
>
> Excellente nouvelle : **{{hote}} a accepté votre réservation !** 🎉
> Votre séjour est confirmé et votre paiement a été encaissé.
>
> **Votre séjour**
> - 🏠 **{{hebergement}}**, {{ville}}
> - 📅 Arrivée le **{{date_arrivee}}** · Départ le **{{date_depart}}** ({{nb_nuits}} nuits)
> - 👥 {{nb_voyageurs}} voyageur(s)
> - 🧾 Montant payé : **{{montant_total}}**
> - 💚 Dont **{{contribution_solidaire}}** reversés à **{{projet_solidaire}}** — merci pour votre impact !
>
> [Voir ma réservation]({{lien_reservation}}) · [Contacter mon hôte]({{lien_conversation}})
>
> Votre hôte pourra vous communiquer les détails pratiques (arrivée, clés, accès) via la messagerie.
>
> Bon séjour sur Rodrigues,
> L'équipe BnB Rodrigues 🌴

---

## 5. Demande non retenue (refus de l'hôte)

**Déclencheur :** `ReservationRefused`

- **Objet :** Votre demande pour « {{hebergement}} » n'a pas pu être retenue
- **Pré-en-tête :** Aucun montant n'a été prélevé — d'autres pépites vous attendent.

> Bonjour {{prenom}},
>
> Malheureusement, **{{hote}} n'a pas pu accepter** votre demande pour **{{hebergement}}**
> ({{date_arrivee}} → {{date_depart}}).
>
> Pas d'inquiétude : **aucun montant n'a été prélevé** et l'autorisation de paiement a été libérée.
>
> Rodrigues regorge d'autres hébergements qui pourraient vous séduire :
> [Découvrir d'autres hébergements]({{lien_recherche}})
>
> L'équipe BnB Rodrigues 🌴

---

## 6. Demande expirée (absence de réponse sous 24 h)

**Déclencheur :** refus automatique après 24 h

- **Objet :** Votre demande pour « {{hebergement}} » a expiré
- **Pré-en-tête :** Pas de réponse sous 24 h — réessayons ensemble.

> Bonjour {{prenom}},
>
> Votre hôte n'a pas répondu dans le délai de **24 heures**, votre demande pour
> **{{hebergement}}** a donc expiré.
>
> **Aucun montant n'a été prélevé.** Vous pouvez renouveler votre demande ou explorer d'autres
> hébergements disponibles aux mêmes dates :
> [Voir les disponibilités]({{lien_recherche}})
>
> L'équipe BnB Rodrigues 🌴

---

## 7. Réservation annulée

**Déclencheur :** `ReservationCancelled`

- **Objet :** Votre réservation à {{ville}} a été annulée
- **Pré-en-tête :** Voici le récapitulatif de cette annulation.

> Bonjour {{prenom}},
>
> Votre réservation pour **{{hebergement}}** ({{date_arrivee}} → {{date_depart}}) a été **annulée**.
>
> Si un paiement avait été encaissé, le remboursement est traité selon les conditions d'annulation ;
> vous recevrez une confirmation séparée le cas échéant. Une question ? Votre hôte reste joignable :
> [Voir ma conversation]({{lien_conversation}})
>
> Nous espérons vous accueillir bientôt pour un prochain séjour :
> [Trouver un autre hébergement]({{lien_recherche}})
>
> L'équipe BnB Rodrigues 🌴

---

## 8. Nouveau message de votre hôte

**Déclencheur :** `MessagePosted` (auteur = hôte)

- **Objet :** {{hote}} vous a envoyé un message
- **Pré-en-tête :** À propos de votre séjour « {{hebergement}} ».

> Bonjour {{prenom}},
>
> **{{hote}}** vous a écrit au sujet de **{{hebergement}}** :
>
> > {{extrait_message}}
>
> [Répondre]({{lien_conversation}})
>
> L'équipe BnB Rodrigues 🌴

---

## 9. Votre séjour approche (J-3)

**Déclencheur :** rappel programmé avant la date d'arrivée

- **Objet :** Plus que quelques jours avant Rodrigues 🌴
- **Pré-en-tête :** Tout est prêt pour votre arrivée le {{date_arrivee}}.

> Bonjour {{prenom}},
>
> Votre séjour à **{{hebergement}}**, {{ville}}, commence le **{{date_arrivee}}**. On a hâte pour vous !
>
> **À garder sous la main**
> - 📅 Arrivée le {{date_arrivee}} · Départ le {{date_depart}}
> - 💬 Modalités d'arrivée et accès : [voir avec votre hôte]({{lien_conversation}})
> - 🧳 Pensez à vérifier les informations pratiques de votre hébergement
>
> Envie d'idées sur place ? [Découvrez nos activités à Rodrigues]({{lien_blog}}) — plongée,
> randonnées, excursions et gastronomie créole.
>
> Bon voyage,
> L'équipe BnB Rodrigues 🌴

---

## 10. Partagez votre avis (après le départ)

**Déclencheur :** séjour terminé (date de départ passée, réservation confirmée)

- **Objet :** Comment s'est passé votre séjour à {{ville}} ?
- **Pré-en-tête :** Votre avis aide les futurs voyageurs et votre hôte.

> Bonjour {{prenom}},
>
> Nous espérons que votre séjour à **{{hebergement}}** était à la hauteur de Rodrigues !
>
> Prenez une minute pour **laisser un avis** : votre retour guide les futurs voyageurs et valorise
> le travail de {{hote}}.
>
> [Noter mon séjour]({{lien_avis}})
>
> Merci d'avoir voyagé solidaire avec nous — votre séjour a soutenu **{{projet_solidaire}}**. 💚
>
> À bientôt sur l'île,
> L'équipe BnB Rodrigues 🌴
