import React from 'react';
import { Link } from 'react-router-dom';
import LegalPageLayout, { LegalSection } from './LegalPageLayout';

const LAST_UPDATED = '29 juin 2026';

/**
 * Mentions légales — statutory identification of the site publisher, host and
 * contact. Drafted under Mauritian law. Legal-identity fields are left in
 * [brackets] to be completed once the operating company is registered with the
 * CBRD.
 */
const LegalNoticePage: React.FC = () => (
  <LegalPageLayout title="Mentions légales" lastUpdated={LAST_UPDATED}>
    <p>
      Conformément aux dispositions légales en vigueur, les informations relatives à l'éditeur et à
      l'hébergement de la plateforme ZouriteBnb (la « <strong>Plateforme</strong> ») sont précisées ci-dessous.
    </p>

    <LegalSection id="editeur" title="1. Éditeur">
      <p>
        La Plateforme est éditée par <strong>[Raison sociale]</strong>, [forme juridique] au capital de
        [montant], immatriculée auprès du Corporate and Business Registration Department (CBRD) de Maurice sous
        le Business Registration Number (BRN) <strong>[BRN]</strong>.
      </p>
      <ul className="list-disc pl-6 space-y-1">
        <li>Siège social : [adresse complète, Rodrigues, République de Maurice]</li>
        <li>Numéro de TVA (le cas échéant) : [VAT Registration Number]</li>
        <li>
          Courriel :{' '}
          <a className="text-primary-600 hover:underline" href="mailto:contact@zouritebnb.com">
            contact@zouritebnb.com
          </a>
        </li>
        <li>Téléphone : [numéro de téléphone]</li>
      </ul>
    </LegalSection>

    <LegalSection id="publication" title="2. Directeur de la publication">
      <p>Le directeur de la publication est [Nom et qualité du représentant légal].</p>
    </LegalSection>

    <LegalSection id="hebergement" title="3. Hébergement">
      <p>
        Le site est hébergé par <strong>[Nom de l'hébergeur]</strong>, [adresse de l'hébergeur], joignable au
        [contact de l'hébergeur].
      </p>
    </LegalSection>

    <LegalSection id="propriete" title="4. Propriété intellectuelle">
      <p>
        La marque ZouriteBnb, le logo, la charte graphique, les textes et l'ensemble des éléments composant la
        Plateforme sont protégés par le droit de la propriété intellectuelle et demeurent la propriété exclusive
        de l'éditeur ou de ses partenaires. Toute reproduction ou représentation, totale ou partielle, sans
        autorisation préalable écrite, est interdite. Les contenus publiés par les Hôtes (photos, descriptions)
        relèvent de la responsabilité de leurs auteurs.
      </p>
    </LegalSection>

    <LegalSection id="paiement" title="5. Prestataire de paiement">
      <p>
        Les paiements en ligne sont traités par <strong>Stripe</strong>. ZouriteBnb n'a pas accès aux données
        complètes des cartes bancaires des utilisateurs.
      </p>
    </LegalSection>

    <LegalSection id="donnees" title="6. Données personnelles et cookies">
      <p>
        Le traitement des données personnelles est décrit dans notre{' '}
        <Link className="text-primary-600 hover:underline" to="/confidentialite">
          Politique de confidentialité
        </Link>
        . Les conditions d'utilisation de la Plateforme figurent dans les{' '}
        <Link className="text-primary-600 hover:underline" to="/cgu">
          Conditions Générales d'Utilisation
        </Link>{' '}
        et les{' '}
        <Link className="text-primary-600 hover:underline" to="/cgv">
          Conditions Générales de Vente
        </Link>
        .
      </p>
    </LegalSection>

    <LegalSection id="droit-applicable" title="7. Droit applicable">
      <p>
        Les présentes mentions légales sont régies par le droit mauricien. Pour toute question, vous pouvez nous
        écrire à{' '}
        <a className="text-primary-600 hover:underline" href="mailto:contact@zouritebnb.com">
          contact@zouritebnb.com
        </a>
        .
      </p>
    </LegalSection>
  </LegalPageLayout>
);

export default LegalNoticePage;
