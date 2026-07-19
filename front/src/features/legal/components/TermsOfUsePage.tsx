import React from 'react';
import { Link } from 'react-router-dom';
import LegalPageLayout, { LegalSection } from './LegalPageLayout';

const LAST_UPDATED = '19 juillet 2026';

/**
 * Conditions Générales d'Utilisation (CGU) — governs how everyone uses the
 * ZouriteBnb platform (account, content, conduct). Drafted under Mauritian law.
 * Legal-identity fields are left in [brackets] to be completed once the
 * operating company is registered with the CBRD.
 */
const TermsOfUsePage: React.FC = () => (
  <LegalPageLayout title="Conditions Générales d'Utilisation" lastUpdated={LAST_UPDATED}>
    <p>
      Les présentes Conditions Générales d'Utilisation (les « <strong>CGU</strong> ») régissent l'accès et
      l'utilisation de la plateforme ZouriteBnb, accessible sur le site et l'application du même nom (la
      « <strong>Plateforme</strong> »). En créant un compte ou en utilisant la Plateforme, vous acceptez sans
      réserve les présentes CGU. Si vous n'y consentez pas, vous ne devez pas utiliser la Plateforme.
    </p>

    <LegalSection id="editeur" title="1. Éditeur de la Plateforme">
      <p>
        La Plateforme est éditée par <strong>[Raison sociale]</strong>, [forme juridique] au capital de
        [montant], immatriculée auprès du Corporate and Business Registration Department (CBRD) de Maurice sous
        le Business Registration Number (BRN) <strong>[BRN]</strong>, dont le siège social est situé
        [adresse complète, Rodrigues, République de Maurice] (ci-après « <strong>ZouriteBnb</strong> », « nous »).
      </p>
      <p>
        Directeur de la publication : [Nom du représentant légal]. Contact :{' '}
        <a className="text-primary-600 hover:underline" href="mailto:contact@zouritebnb.com">
          contact@zouritebnb.com
        </a>
        . Hébergement du site : [Nom et adresse de l'hébergeur].
      </p>
    </LegalSection>

    <LegalSection id="objet" title="2. Objet et rôle de la Plateforme">
      <p>
        ZouriteBnb est une <strong>place de marché de mise en relation</strong> qui permet à des hôtes
        (les « <strong>Hôtes</strong> ») de proposer à la location de courte durée des hébergements situés à
        Rodrigues, et à des voyageurs (les « <strong>Voyageurs</strong> ») de les réserver.
      </p>
      <p>
        ZouriteBnb agit exclusivement en qualité d'<strong>intermédiaire technique</strong>. Le contrat de
        location est conclu directement et uniquement entre l'Hôte et le Voyageur. ZouriteBnb n'est ni
        propriétaire, ni gestionnaire, ni loueur des hébergements, et n'est pas partie au contrat de location.
        Les conditions financières et de réservation sont précisées dans les{' '}
        <Link className="text-primary-600 hover:underline" to="/cgv">
          Conditions Générales de Vente
        </Link>
        .
      </p>
    </LegalSection>

    <LegalSection id="acces" title="3. Accès et compte utilisateur">
      <p>
        La consultation des annonces est libre. La réservation, la publication d'une annonce et la messagerie
        nécessitent la création d'un compte. Vous devez avoir au moins 18 ans et la capacité juridique de
        contracter.
      </p>
      <p>
        Vous vous engagez à fournir des informations exactes et à les tenir à jour. Vous êtes seul responsable de
        la confidentialité de vos identifiants et de toute activité réalisée depuis votre compte. Toute
        utilisation frauduleuse doit nous être signalée sans délai.
      </p>
      <p>
        Le compte peut être créé avec une adresse email et un mot de passe, ou via un{' '}
        <strong>fournisseur d'identité tiers</strong> (Google, Apple ou Facebook) lorsque cette option est
        proposée. Dans ce cas, l'adresse email transmise par le fournisseur est utilisée pour identifier votre
        compte, et l'accès à ce fournisseur relève de votre responsabilité et des conditions dudit fournisseur.
      </p>
      <p>
        Pour certaines opérations, une <strong>vérification d'identité</strong> peut être requise afin de
        sécuriser la communauté. Vous autorisez ZouriteBnb à effectuer les vérifications raisonnables permettant
        de confirmer votre identité.
      </p>
    </LegalSection>

    <LegalSection id="obligations-hotes" title="4. Obligations des Hôtes">
      <p>Tout Hôte qui publie une annonce s'engage à :</p>
      <ul className="list-disc pl-6 space-y-1">
        <li>
          disposer du droit de proposer l'hébergement à la location et respecter la réglementation applicable à
          Rodrigues et à Maurice (autorisations, licences touristiques, normes de sécurité, fiscalité) ;
        </li>
        <li>décrire l'hébergement de manière exacte, sincère et non trompeuse (photos, équipements, capacité, localisation) ;</li>
        <li>tenir à jour son calendrier de disponibilités et ses tarifs ;</li>
        <li>honorer les réservations confirmées et accueillir le Voyageur dans les conditions annoncées ;</li>
        <li>répondre aux demandes des Voyageurs dans un délai raisonnable.</li>
      </ul>
    </LegalSection>

    <LegalSection id="obligations-voyageurs" title="5. Obligations des Voyageurs">
      <ul className="list-disc pl-6 space-y-1">
        <li>utiliser l'hébergement « en bon père de famille » et respecter le règlement intérieur de l'Hôte ;</li>
        <li>respecter la capacité d'accueil annoncée et la destination des lieux ;</li>
        <li>signaler tout dommage et en répondre auprès de l'Hôte ;</li>
        <li>régler l'intégralité des sommes dues au titre de la réservation.</li>
      </ul>
    </LegalSection>

    <LegalSection id="contenus" title="6. Contenus publiés par les utilisateurs">
      <p>
        Vous demeurez responsable des contenus que vous publiez (annonces, photos, messages, avis). Vous
        garantissez en détenir les droits et que ces contenus ne sont ni illicites, ni diffamatoires, ni
        trompeurs, ni contraires aux droits de tiers.
      </p>
      <p>
        En publiant un contenu, vous concédez à ZouriteBnb une licence non exclusive, gratuite et pour le monde
        entier de l'héberger, l'afficher et le reproduire aux seules fins d'exploitation et de promotion de la
        Plateforme. ZouriteBnb peut retirer tout contenu manifestement illicite ou contraire aux présentes CGU.
      </p>
    </LegalSection>

    <LegalSection id="avis" title="7. Avis et réponses des Hôtes">
      <p>
        À l'issue d'un séjour, le Voyageur peut publier un avis. L'Hôte dispose d'un droit de réponse publique.
        Les avis doivent être sincères, reposer sur une expérience réelle et rester courtois. ZouriteBnb se
        réserve le droit de modérer les avis manifestement abusifs, injurieux ou frauduleux.
      </p>
    </LegalSection>

    <LegalSection id="conduite" title="8. Comportements interdits">
      <p>Il est notamment interdit de :</p>
      <ul className="list-disc pl-6 space-y-1">
        <li>contourner la Plateforme pour conclure ou régler une réservation hors de celle-ci ;</li>
        <li>publier des contenus faux, illicites, haineux ou portant atteinte à la vie privée d'autrui ;</li>
        <li>extraire ou collecter des données de manière automatisée (scraping), ou compromettre la sécurité du service ;</li>
        <li>usurper l'identité d'un tiers ou créer des comptes multiples à des fins frauduleuses.</li>
      </ul>
    </LegalSection>

    <LegalSection id="propriete" title="9. Propriété intellectuelle">
      <p>
        La marque ZouriteBnb, le logo, les textes, l'interface et les éléments de la Plateforme sont protégés et
        demeurent la propriété exclusive de ZouriteBnb ou de ses partenaires. Toute reproduction sans
        autorisation préalable est interdite.
      </p>
    </LegalSection>

    <LegalSection id="responsabilite" title="10. Responsabilité">
      <p>
        ZouriteBnb fournit un service de mise en relation et met en œuvre les moyens raisonnables pour assurer la
        disponibilité de la Plateforme, sans garantie d'absence d'interruption. ZouriteBnb n'étant pas partie au
        contrat de location, sa responsabilité ne saurait être engagée au titre de l'exécution de la prestation
        d'hébergement, de l'exactitude des annonces ou du comportement des utilisateurs.
      </p>
    </LegalSection>

    <LegalSection id="donnees" title="11. Données personnelles">
      <p>
        ZouriteBnb traite vos données personnelles conformément au Data Protection Act 2017 (Maurice). Les
        données collectées sont nécessaires à la gestion de votre compte, des réservations et de la relation
        commerciale. Vous disposez d'un droit d'accès, de rectification et d'effacement de vos données, exerçable
        à l'adresse{' '}
        <a className="text-primary-600 hover:underline" href="mailto:contact@zouritebnb.com">
          contact@zouritebnb.com
        </a>
        . Le détail des traitements figure dans notre Politique de confidentialité.
      </p>
    </LegalSection>

    <LegalSection id="suspension" title="12. Suspension et résiliation">
      <p>
        Vous pouvez fermer votre compte à tout moment. ZouriteBnb peut suspendre ou résilier l'accès d'un
        utilisateur qui manquerait aux présentes CGU, sans préjudice des réservations en cours et des sommes dues.
      </p>
    </LegalSection>

    <LegalSection id="modification" title="13. Modification des CGU">
      <p>
        ZouriteBnb peut faire évoluer les présentes CGU. La version applicable est celle en vigueur à la date de
        votre utilisation de la Plateforme. En cas de modification substantielle, vous en serez informé par un
        moyen approprié.
      </p>
    </LegalSection>

    <LegalSection id="droit-applicable" title="14. Droit applicable et juridiction">
      <p>
        Les présentes CGU sont régies par le droit mauricien. Tout litige relatif à leur interprétation ou à leur
        exécution relève de la compétence exclusive des tribunaux compétents de la République de Maurice, après
        recherche d'une solution amiable.
      </p>
    </LegalSection>
  </LegalPageLayout>
);

export default TermsOfUsePage;
