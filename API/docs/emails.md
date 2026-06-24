# Notifications (emails & SMS)

Référence de toutes les notifications envoyées par BnB Rodrigues : **quand** (déclencheur),
**pourquoi** (raison métier), **à qui** (destinataire) et **quel contenu** (objet + corps).

> Cette page consolide les références `docs/emails-voyageur.md` et `docs/emails-hote.md`
> mentionnées dans le code (`TravelerEmails`, `HostEmails`).

## Comment ça marche

Toutes les notifications passent par un **outbox transactionnel** (pattern boîte d'envoi) :

1. Un **événement de domaine** est publié (`UserRegistered`, `ReservationRequested`…).
2. Un **listener** Symfony Messenger (`src/Notification/Application/Listener/Send*.php`) réagit,
   construit le contenu et appelle `QueueEmail` / `QueueSms`.
3. Le message est **persisté** en base (tables `outbox_email` / `outbox_sms`), pas envoyé tout de suite.
4. Une **commande console** planifiée relaie les messages en attente :
   - `app:emails:send-pending` → `SymfonyEmailSender` (SMTP via Symfony Mailer)
   - `app:sms:send-pending` → `SmsSender`
5. Le statut est enregistré (Envoyé / Échec) avec **retry** en cas d'échec.

**Expéditeur** (`.env`) : `BnB Rodrigues <bonjour@bnb-rodrigues.com>`
(`MAILER_FROM_NAME` / `MAILER_FROM_EMAIL`, injectés dans `config/services.yaml:191`).

**Gabarit commun** : tous les emails héritent de `templates/emails/base.html.twig`
(bandeau bleu « BnB Rodrigues 🌴 », signature « L'équipe BnB Rodrigues 🌴 », mention
« Vous recevez cet email car vous avez un compte sur BnB Rodrigues. »).

**Définition du contenu** (objet + variables) :
- `src/Notification/Application/Email/TravelerEmails.php` — emails voyageur
- `src/Notification/Application/Email/HostEmails.php` — emails hôte
- `src/Notification/Application/Email/MessageEmails.php` — email message
- `src/Notification/Application/Sms/HostSms.php` — SMS hôte

---

## Vue d'ensemble

| # | Notification | Canal | Déclencheur (événement) | Destinataire |
|---|--------------|-------|-------------------------|--------------|
| 1 | Bienvenue | Email | `UserRegistered` (inscription) | Nouvel inscrit |
| 2 | Demande envoyée | Email | `ReservationRequested` | Voyageur |
| 3 | Nouvelle demande | Email | `ReservationRequested` | Hôte(s) de l'équipe |
| 4 | Nouvelle demande | **SMS** | `ReservationRequested` | Hôte(s) avec téléphone |
| 5 | Réservation confirmée | Email | `ReservationConfirmed` | Voyageur |
| 6 | Demande non retenue | Email | `ReservationRefused` (refus manuel) | Voyageur |
| 7 | Demande expirée | Email | `ReservationRefused` (timeout 24h) | Voyageur |
| 8 | Réservation annulée | Email | `ReservationCancelled` | Voyageur |
| 9 | Réservation annulée | Email | `ReservationCancelled` | Hôte(s) de l'équipe |
| 10 | Invitation co-hôte | Email | `CoHostInvited` | Personne invitée |
| 11 | Nouveau message | Email | `MessagePosted` (hors système) | L'autre partie de la conversation |

---

## Emails voyageur

### 1. Bienvenue

- **Quand** : juste après la création du compte (`UserRegistered`).
- **Pourquoi** : accueillir le nouvel inscrit, confirmer que le compte est actif, rappeler la mission solidaire.
- **Listener** : `SendWelcomeEmailOnUserRegistered.php`
- **Template** : `templates/emails/traveler/welcome.html.twig`
- **Objet** : `Bienvenue sur BnB Rodrigues 🌴`
- **Variables** : `greetingName`
- **Corps** :
  > Bonjour {greetingName}, bienvenue sur **BnB Rodrigues** ! Votre compte est désormais actif.
  > Ici, chaque séjour réservé soutient un projet solidaire local — voyager rime avec impact positif.
  > Découvrez les hébergements de l'île et trouvez votre prochain coup de cœur.

### 2. Demande de réservation envoyée

- **Quand** : le voyageur soumet une demande de réservation (`ReservationRequested`).
- **Pourquoi** : confirmer la bonne transmission de la demande et expliquer le mécanisme de mise en attente 24h (aucun prélèvement avant acceptation).
- **Listener** : `SendRequestedEmailOnReservationRequested.php`
- **Template** : `templates/emails/traveler/reservation_requested.html.twig`
- **Objet** : `Votre demande pour « {accommodationTitle} » a bien été envoyée`
- **Variables** : `greetingName`, `accommodationTitle`, `city` (optionnel), `checkIn`, `checkOut`, `nights`
- **Corps** :
  > Votre **demande de réservation** pour « {accommodationTitle} »[, {city}] a bien été transmise à votre hôte. 🙌
  > 📅 Du **{checkIn}** au **{checkOut}** ({nights} nuit(s)).
  > Votre hôte dispose de **24 heures** pour répondre. **Aucun montant ne sera prélevé tant que votre hôte n'a pas accepté** ; à défaut, l'autorisation de paiement est automatiquement libérée.

### 3. Réservation confirmée

- **Quand** : l'hôte accepte la demande (`ReservationConfirmed`).
- **Pourquoi** : confirmer que le séjour est réservé et que le paiement a été encaissé.
- **Listener** : `SendConfirmationEmailOnReservationConfirmed.php`
- **Template** : `templates/emails/traveler/reservation_confirmed.html.twig`
- **Objet** : `C'est confirmé ! Votre séjour « {accommodationTitle} » est réservé 🎉`
- **Variables** : `greetingName`, `accommodationTitle`, `city` (optionnel), `checkIn`, `checkOut`, `nights`
- **Corps** :
  > Excellente nouvelle : votre hôte a **accepté votre réservation** pour « {accommodationTitle} »[, {city}] ! 🎉
  > 📅 Du **{checkIn}** au **{checkOut}** ({nights} nuit(s)).
  > Votre séjour est confirmé et votre paiement a été encaissé. Votre hôte vous communiquera les détails pratiques (arrivée, clés, accès) via la messagerie.
  > Merci d'avoir voyagé solidaire — votre séjour soutient un projet local de Rodrigues. 💚

### 4. Demande non retenue (refus de l'hôte)

- **Quand** : l'hôte refuse explicitement la demande — `ReservationRefused` avec `isAutomatic = false`.
- **Pourquoi** : informer le voyageur du refus, le rassurer (aucun prélèvement, autorisation libérée).
- **Listener** : `SendRefusalEmailOnReservationRefused.php` (aiguille selon `isAutomatic`)
- **Template** : `templates/emails/traveler/reservation_refused.html.twig`
- **Objet** : `Votre demande pour « {accommodationTitle} » n'a pas pu être retenue`
- **Variables** : `greetingName`, `accommodationTitle`
- **Corps** :
  > Malheureusement, votre hôte n'a pas pu accepter votre demande pour « {accommodationTitle} ».
  > Pas d'inquiétude : **aucun montant n'a été prélevé** et l'autorisation de paiement a été libérée.
  > Rodrigues regorge d'autres hébergements qui pourraient vous séduire.

### 5. Demande expirée (timeout 24h)

- **Quand** : l'hôte ne répond pas sous 24h — `ReservationRefused` avec `isAutomatic = true`.
- **Pourquoi** : informer le voyageur de l'expiration automatique, l'inviter à renouveler ou chercher ailleurs. Distinct du refus manuel (#4).
- **Listener** : `SendRefusalEmailOnReservationRefused.php`
- **Template** : `templates/emails/traveler/reservation_expired.html.twig`
- **Objet** : `Votre demande pour « {accommodationTitle} » a expiré`
- **Variables** : `greetingName`, `accommodationTitle`
- **Corps** :
  > Votre hôte n'a pas répondu dans le délai de **24 heures**, votre demande pour « {accommodationTitle} » a donc expiré.
  > **Aucun montant n'a été prélevé.** Vous pouvez renouveler votre demande ou explorer d'autres hébergements disponibles aux mêmes dates.

### 6. Réservation annulée (voyageur)

- **Quand** : une réservation confirmée est annulée (`ReservationCancelled`).
- **Pourquoi** : notifier l'annulation et clarifier le statut du remboursement.
- **Listener** : `SendCancellationEmailOnReservationCancelled.php`
- **Template** : `templates/emails/traveler/reservation_cancelled.html.twig`
- **Objet** : `Votre réservation a été annulée`
- **Variables** : `greetingName`, `accommodationTitle`
- **Corps** :
  > Votre réservation pour « {accommodationTitle} » a été **annulée**.
  > Si un paiement avait été encaissé, le remboursement est traité selon les conditions d'annulation ; vous recevrez le cas échéant une confirmation séparée.
  > Nous espérons vous accueillir bientôt pour un prochain séjour.

---

## Emails hôte

### 7. Nouvelle demande de réservation

- **Quand** : un voyageur soumet une demande (`ReservationRequested`).
- **Pourquoi** : alerter chaque hôte/co-hôte de l'équipe qu'une demande attend une décision (24h).
- **Listener** : `SendHostRequestEmailOnReservationRequested.php` (boucle sur les contacts de l'équipe)
- **Template** : `templates/emails/host/reservation_requested.html.twig`
- **Objet** : `Nouvelle demande de réservation pour « {accommodationTitle} »`
- **Variables** : `greetingName`, `guestName`, `accommodationTitle`, `city` (optionnel), `checkIn`, `checkOut`, `nights`
- **Corps** :
  > Vous avez reçu une **nouvelle demande de réservation** de {guestName} pour « {accommodationTitle} »[, {city}].
  > 📅 Du **{checkIn}** au **{checkOut}** ({nights} nuit(s)).
  > ⏳ Vous disposez de **24 heures** pour accepter ou refuser cette demande. Sans réponse, elle sera automatiquement refusée et l'autorisation de paiement libérée.

### 8. Réservation annulée (hôte)

- **Quand** : une réservation confirmée est annulée (`ReservationCancelled`).
- **Pourquoi** : informer les hôtes que les dates se libèrent à nouveau.
- **Listener** : `SendHostCancellationEmailOnReservationCancelled.php` (boucle sur les contacts de l'équipe)
- **Template** : `templates/emails/host/reservation_cancelled.html.twig`
- **Objet** : `Réservation annulée — « {accommodationTitle} »`
- **Variables** : `greetingName`, `guestName`, `accommodationTitle`, `checkIn`, `checkOut`
- **Corps** :
  > La réservation de {guestName} pour « {accommodationTitle} » a été **annulée**.
  > 📅 Séjour initialement prévu du **{checkIn}** au **{checkOut}**.
  > Ces dates sont de nouveau disponibles à la réservation sur votre hébergement.

### 9. Invitation co-hôte

- **Quand** : un hôte invite une personne à rejoindre son équipe (`CoHostInvited`).
- **Pourquoi** : inviter un futur co-hôte à créer un compte avec l'adresse invitée.
- **Listener** : `SendCoHostInvitationEmailOnCoHostInvited.php`
- **Template** : `templates/emails/host/cohost_invitation.html.twig`
- **Objet** : `Vous êtes invité(e) comme co-hôte sur BnB Rodrigues`
- **Variables** : `invitedEmail`
- **Destinataire** : l'adresse invitée (la personne n'a pas forcément encore de compte → pas de `greetingName`, salutation neutre « Bonjour, »).
- **Corps** :
  > Vous avez été invité(e) à devenir **co-hôte** sur **BnB Rodrigues**.
  > En rejoignant l'équipe, vous pourrez gérer les hébergements, les réservations et les échanges avec les voyageurs.
  > Pour accepter l'invitation, créez votre compte avec cette adresse email ({invitedEmail}) sur BnB Rodrigues.

---

## Email message (bidirectionnel)

### 10. Nouveau message

- **Quand** : un message est posté dans une conversation (`MessagePosted`). Les **messages système sont exclus**.
- **Pourquoi** : notifier l'autre partie de la conversation. Le rédacteur ne reçoit jamais de copie de son propre message.
- **Listener** : `SendMessageEmailOnMessagePosted.php`
  - Si le **voyageur** écrit → notification à **tous les hôtes** de l'équipe.
  - Si un **hôte** écrit → notification au **voyageur** uniquement.
  - Le co-hôte auteur du message est exclu des destinataires.
- **Template** : `templates/emails/message_posted.html.twig`
- **Objet** : `Nouveau message de {senderName}`
  (`senderName` = nom affiché de l'auteur, avec repli « L'hôte » / « Le voyageur »)
- **Variables** : `greetingName`, `senderName`, `accommodationTitle` (optionnel), `messageBody`
- **Corps** :
  > Vous avez reçu un nouveau message de **{senderName}**[ à propos de « {accommodationTitle} »].
  > [bloc cité] {messageBody}
  > Connectez-vous à votre messagerie BnB Rodrigues pour répondre.

---

## SMS hôte

### 11. Nouvelle demande de réservation (SMS)

- **Quand** : un voyageur soumet une demande (`ReservationRequested`), **en parallèle** de l'email hôte (#7).
- **Pourquoi** : alerter rapidement les hôtes pour respecter le délai de réponse de 24h.
- **Listener** : `SendHostSmsOnReservationRequested.php` — boucle sur les contacts de l'équipe, **ignore ceux sans numéro de téléphone**.
- **Constructeur du texte** : `HostSms::reservationRequested()`
- **Texte** (plein texte, court) :
  > BnB Rodrigues : nouvelle demande de réservation de {guestName} pour « {accommodationTitle} » du {checkIn} au {checkOut}. Vous avez 24h pour répondre.

---

## Index des fichiers

| Élément | Chemin |
|---------|--------|
| Définitions emails | `src/Notification/Application/Email/{TravelerEmails,HostEmails,MessageEmails}.php` |
| Définition SMS | `src/Notification/Application/Sms/HostSms.php` |
| Listeners | `src/Notification/Application/Listener/Send*.php` |
| Templates email | `templates/emails/{base,message_posted}.html.twig`, `templates/emails/{traveler,host}/*.html.twig` |
| Outbox email | `src/Notification/Application/UseCase/{QueueEmail,SendPendingEmails}.php`, `src/Notification/Domain/Entity/OutboxEmail.php` |
| Outbox SMS | `src/Notification/Application/UseCase/{QueueSms,SendPendingSms}.php`, `src/Notification/Domain/Entity/OutboxSms.php` |
| Envoi SMTP | `src/Notification/Infrastructure/Mailer/SymfonyEmailSender.php` |
| Commandes console | `src/Notification/Infrastructure/Console/{SendPendingEmailsCommand,SendPendingSmsCommand}.php` |
| Configuration | `config/packages/mailer.yaml`, `config/services.yaml:191`, `.env` (`MAILER_*`) |
