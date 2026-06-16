# Emails transactionnels — Parcours hôte

> Contenu des emails envoyés à l'**hôte** (et au co-hôte invité) aux étapes clés.
> Pendant du document `emails-voyageur.md`. Langue : français. Marque : **BnB Rodrigues**.

## Conventions

Identiques au parcours voyageur : expéditeur `BnB Rodrigues <bonjour@bnb-rodrigues.com>`,
ton chaleureux et professionnel, signature *L'équipe BnB Rodrigues 🌴*, pied de page commun.

### Mise en œuvre

Mêmes mécanismes que les emails voyageur (cf. `emails-voyageur.md`) :

- **Vues HTML** : le corps de chaque email est une **vue Twig** sous
  `API/templates/emails/host/*.html.twig`, qui étend `emails/base.html.twig`.
  Le builder `HostEmails` ne produit que `(template, sujet, variables)` ; le rendu HTML est
  effectué par le port `EmailRenderer` (adaptateur `TwigEmailRenderer`) au moment de la mise
  en file dans l'outbox.
- **Outbox** : un listener réagit à l'événement, résout les destinataires (les membres de
  l'équipe via `TeamContactProvider`), et met l'email en file `outbox_email` (statut `pending`).
- **Relais & renvoi** : `app:emails:send-pending` envoie, `app:emails:resend <id>` renvoie.

### Vue d'ensemble

| # | Déclencheur (événement) | Destinataire | Email |
|---|--------------------------|--------------|-------|
| 1 | `ReservationRequested` | hôte(s) de l'équipe | Nouvelle demande de réservation |
| 2 | `ReservationCancelled` | hôte(s) de l'équipe | Réservation annulée |
| 3 | `CoHostInvited` | adresse invitée | Invitation à rejoindre l'équipe |
| 4 | `MessagePosted` (du voyageur) | hôte(s) de l'équipe | Nouveau message du voyageur |

> **Messagerie** — chaque message non-système posté par le **voyageur** est notifié par email à
> tous les hôtes de l'équipe (vue `emails/message_posted.html.twig`, partagée avec le sens
> hôte→voyageur). L'auteur n'est jamais notifié de son propre message.

### Notification SMS (demande de réservation)

En plus de l'email #1, une **notification SMS** est envoyée à chaque hôte de l'équipe disposant
d'un numéro de téléphone (`user.phone_number`) lors d'une `ReservationRequested`.

- **Outbox** (comme les emails) : le listener `SendHostSmsOnReservationRequested` met le SMS en
  file dans la table `outbox_sms` (via `QueueSms`, statut `pending`). Le relais
  `app:sms:send-pending` (`SendPendingSms`) l'envoie via le port `SmsSender`, avec reprises
  bornées (`pending → failed` après `maxAttempts`). Texte court via `HostSms` ; les hôtes sans
  numéro sont ignorés.
- **Aucun provider pour le moment** : l'adaptateur `LogSmsSender` se contente de journaliser le
  SMS. Le brancher sur un vrai fournisseur (Twilio, Vonage, OVH…) = remplacer cet adaptateur.
- Le statut d'outbox est mutualisé (`OutboxStatus`) entre emails et SMS.

---

## 1. Nouvelle demande de réservation

**Déclencheur :** `ReservationRequested` · **Vue :** `host/reservation_requested.html.twig`

- **Objet :** Nouvelle demande de réservation pour « {{accommodationTitle}} »
- **Pré-en-tête :** {{guestName}} souhaite réserver — vous avez 24 h pour répondre.

> Bonjour {{greetingName}},
>
> Vous avez reçu une **nouvelle demande de réservation** de {{guestName}} pour
> « {{accommodationTitle}} »{{, city}}.
>
> 📅 Du **{{checkIn}}** au **{{checkOut}}** ({{nights}} nuits).
>
> ⏳ Vous disposez de **24 heures** pour accepter ou refuser. Sans réponse, la demande est
> automatiquement refusée et l'autorisation de paiement libérée.

---

## 2. Réservation annulée

**Déclencheur :** `ReservationCancelled` · **Vue :** `host/reservation_cancelled.html.twig`

- **Objet :** Réservation annulée — « {{accommodationTitle}} »
- **Pré-en-tête :** Les dates se libèrent sur votre hébergement.

> Bonjour {{greetingName}},
>
> La réservation de {{guestName}} pour « {{accommodationTitle}} » a été **annulée**.
>
> 📅 Séjour initialement prévu du **{{checkIn}}** au **{{checkOut}}**.
>
> Ces dates sont de nouveau disponibles à la réservation sur votre hébergement.

---

## 3. Invitation à rejoindre l'équipe (co-hôte)

**Déclencheur :** `CoHostInvited` · **Vue :** `host/cohost_invitation.html.twig`

> L'adresse invitée est portée par l'événement : l'invité n'a pas forcément encore de compte.

- **Objet :** Vous êtes invité(e) comme co-hôte sur BnB Rodrigues
- **Pré-en-tête :** Rejoignez l'équipe pour gérer hébergements et réservations.

> Bonjour,
>
> Vous avez été invité(e) à devenir **co-hôte** sur **BnB Rodrigues**.
>
> En rejoignant l'équipe, vous pourrez gérer les hébergements, les réservations et les échanges
> avec les voyageurs.
>
> Pour accepter l'invitation, créez votre compte avec cette adresse email ({{invitedEmail}}).
