import React from 'react';
import { Link } from 'react-router-dom';
import LegalPageLayout, { LegalSection } from './LegalPageLayout';

const LAST_UPDATED = '29 juin 2026';

/**
 * Politique de confidentialité — describes how ZouriteBnb processes personal
 * data, under the Mauritius Data Protection Act 2017. Reflects the data the
 * platform actually handles: account, identity verification, reservations,
 * messaging, Stripe payments, wishlist cookie.
 */
const PrivacyPolicyPage: React.FC = () => (
  <LegalPageLayout title="Politique de confidentialité" lastUpdated={LAST_UPDATED}>
    <p>
      La présente Politique de confidentialité explique comment ZouriteBnb (la « <strong>Plateforme</strong> »)
      collecte, utilise et protège vos données personnelles, conformément au <strong>Data Protection Act 2017</strong>{' '}
      de la République de Maurice. Elle complète les{' '}
      <Link className="text-primary-600 hover:underline" to="/cgu">
        Conditions Générales d'Utilisation
      </Link>
      .
    </p>

    <LegalSection id="responsable" title="1. Responsable du traitement">
      <p>
        Le responsable du traitement est <strong>[Raison sociale]</strong> (BRN <strong>[BRN]</strong>), siège à
        [adresse, Rodrigues, République de Maurice]. Pour toute question relative à vos données, écrivez à{' '}
        <a className="text-primary-600 hover:underline" href="mailto:contact@zouritebnb.com">
          contact@zouritebnb.com
        </a>
        .
      </p>
    </LegalSection>

    <LegalSection id="donnees-collectees" title="2. Données collectées">
      <p>Selon votre utilisation de la Plateforme, nous traitons :</p>
      <ul className="list-disc pl-6 space-y-1">
        <li><strong>Données de compte</strong> : nom, adresse e-mail, mot de passe (stocké de façon chiffrée), rôle (Voyageur / Hôte) ;</li>
        <li><strong>Données de profil</strong> : photo, informations renseignées librement ;</li>
        <li><strong>Vérification d'identité</strong> : éléments fournis pour confirmer votre identité, lorsqu'elle est requise ;</li>
        <li><strong>Annonces et réservations</strong> : hébergements, dates, nombre de voyageurs, historique des séjours ;</li>
        <li><strong>Messagerie</strong> : contenu des conversations échangées entre Hôtes et Voyageurs via la Plateforme ;</li>
        <li><strong>Avis</strong> : notes et commentaires publiés, et les réponses des Hôtes ;</li>
        <li><strong>Données de paiement</strong> : traitées par Stripe ; nous ne conservons pas les numéros complets de carte bancaire ;</li>
        <li><strong>Données techniques</strong> : adresse IP, type de navigateur, journaux de connexion et cookies.</li>
      </ul>
    </LegalSection>

    <LegalSection id="finalites" title="3. Finalités et bases légales">
      <p>Vos données sont utilisées pour :</p>
      <ul className="list-disc pl-6 space-y-1">
        <li>créer et gérer votre compte, et permettre la mise en relation (exécution du contrat) ;</li>
        <li>traiter les réservations, paiements et remboursements (exécution du contrat) ;</li>
        <li>assurer la messagerie, les avis et le support utilisateur (intérêt légitime) ;</li>
        <li>prévenir la fraude et sécuriser la Plateforme, y compris la vérification d'identité (intérêt légitime, obligation légale) ;</li>
        <li>vous adresser des informations relatives au service ; les communications promotionnelles ne sont envoyées qu'avec votre consentement.</li>
      </ul>
    </LegalSection>

    <LegalSection id="destinataires" title="4. Destinataires des données">
      <p>Vos données peuvent être partagées avec :</p>
      <ul className="list-disc pl-6 space-y-1">
        <li>l'autre partie à une réservation (un Hôte et un Voyageur partagent les informations nécessaires au séjour) ;</li>
        <li>nos prestataires techniques agissant pour notre compte : Stripe (paiement), hébergeur du site, services d'e-mail ;</li>
        <li>les autorités compétentes lorsque la loi l'exige.</li>
      </ul>
      <p>Nous ne vendons pas vos données personnelles à des tiers.</p>
    </LegalSection>

    <LegalSection id="transferts" title="5. Transferts hors de Maurice">
      <p>
        Certains prestataires (notamment Stripe) peuvent traiter des données en dehors de la République de
        Maurice. Dans ce cas, nous veillons à ce que des garanties appropriées encadrent ces transferts,
        conformément au Data Protection Act 2017.
      </p>
    </LegalSection>

    <LegalSection id="conservation" title="6. Durée de conservation">
      <p>
        Vos données sont conservées le temps nécessaire aux finalités décrites, puis archivées ou supprimées.
        Les données de compte sont conservées tant que le compte est actif ; les données liées aux réservations
        et aux paiements sont conservées pour la durée requise par les obligations comptables et légales
        applicables.
      </p>
    </LegalSection>

    <LegalSection id="droits" title="7. Vos droits">
      <p>
        Conformément au Data Protection Act 2017, vous disposez d'un droit d'accès, de rectification,
        d'effacement, d'opposition et de limitation du traitement de vos données, ainsi que du droit de retirer
        votre consentement à tout moment. Vous pouvez exercer ces droits en écrivant à{' '}
        <a className="text-primary-600 hover:underline" href="mailto:contact@zouritebnb.com">
          contact@zouritebnb.com
        </a>
        . Vous avez également le droit d'introduire une réclamation auprès du Data Protection Office de Maurice.
      </p>
    </LegalSection>

    <LegalSection id="cookies" title="8. Cookies">
      <p>
        La Plateforme utilise des cookies et technologies similaires pour assurer son fonctionnement (par
        exemple mémoriser votre session ou votre liste de favoris), mesurer l'audience et améliorer le service.
        Vous pouvez configurer votre navigateur pour refuser les cookies non essentiels ; certaines
        fonctionnalités peuvent alors être limitées.
      </p>
    </LegalSection>

    <LegalSection id="securite" title="9. Sécurité">
      <p>
        Nous mettons en œuvre des mesures techniques et organisationnelles raisonnables pour protéger vos
        données contre tout accès, altération ou divulgation non autorisés (chiffrement des mots de passe,
        connexions sécurisées, accès restreint).
      </p>
    </LegalSection>

    <LegalSection id="modification" title="10. Modification de la politique">
      <p>
        Nous pouvons faire évoluer la présente Politique de confidentialité. La version applicable est celle
        publiée sur la Plateforme. En cas de modification substantielle, vous en serez informé par un moyen
        approprié.
      </p>
    </LegalSection>
  </LegalPageLayout>
);

export default PrivacyPolicyPage;
