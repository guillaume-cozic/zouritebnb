import React from 'react';
import { Link } from 'react-router-dom';
import LegalPageLayout, { LegalSection } from './LegalPageLayout';

const LAST_UPDATED = '28 juin 2026';

/**
 * Conditions Générales de Vente (CGV) — governs the booking transaction between
 * Voyageur and Hôte, and ZouriteBnb's service fee / payment intermediation.
 * Figures reflect the platform's actual rules: prices in EUR, 8% service fee,
 * Stripe payments, optional solidarity donation, host cancellation with full
 * refund. Drafted under Mauritian law.
 */
const TermsOfSalePage: React.FC = () => (
  <LegalPageLayout title="Conditions Générales de Vente" lastUpdated={LAST_UPDATED}>
    <p>
      Les présentes Conditions Générales de Vente (les « <strong>CGV</strong> ») encadrent la réservation
      d'hébergements proposés par les Hôtes sur la plateforme ZouriteBnb (la « <strong>Plateforme</strong> »).
      Elles complètent les{' '}
      <Link className="text-primary-600 hover:underline" to="/cgu">
        Conditions Générales d'Utilisation
      </Link>
      . Toute réservation implique l'acceptation pleine et entière des présentes CGV.
    </p>

    <LegalSection id="parties" title="1. Parties au contrat de location">
      <p>
        Le contrat de location est conclu directement entre le <strong>Voyageur</strong> et l'
        <strong>Hôte</strong>. ZouriteBnb, éditée par <strong>[Raison sociale]</strong> (BRN{' '}
        <strong>[BRN]</strong>, siège à [adresse, Rodrigues, République de Maurice]), intervient uniquement en
        tant qu'<strong>intermédiaire technique de mise en relation et d'encaissement</strong>. ZouriteBnb n'est
        pas vendeur de la prestation d'hébergement.
      </p>
    </LegalSection>

    <LegalSection id="prix" title="2. Prix">
      <p>
        Les prix sont indiqués en <strong>euros (EUR)</strong> et s'entendent toutes taxes comprises lorsque
        celles-ci sont applicables. Le prix d'un séjour est fixé librement par l'Hôte et peut varier selon les
        dates, la durée et d'éventuelles remises. Le montant total dû par le Voyageur, affiché avant validation,
        comprend :
      </p>
      <ul className="list-disc pl-6 space-y-1">
        <li>le <strong>prix du séjour</strong> fixé par l'Hôte (prix par nuit × nombre de nuits, remises éventuelles déduites) ;</li>
        <li>
          les <strong>frais de service ZouriteBnb</strong>, égaux à <strong>8 %</strong> du prix du séjour,
          rémunérant l'utilisation de la Plateforme ;
        </li>
        <li>
          le cas échéant, un <strong>don solidaire</strong> facultatif au profit d'un projet local, dont le
          montant est choisi et accepté par le Voyageur au moment de la réservation.
        </li>
      </ul>
    </LegalSection>

    <LegalSection id="reservation" title="3. Processus de réservation">
      <p>Une réservation se déroule en plusieurs étapes :</p>
      <ol className="list-decimal pl-6 space-y-1">
        <li>le Voyageur sélectionne un hébergement, des dates disponibles et le nombre de voyageurs ;</li>
        <li>il vérifie le récapitulatif (prix du séjour, frais de service, don éventuel, montant total) ;</li>
        <li>
          il adresse une <strong>demande de réservation</strong> à l'Hôte ou, selon l'annonce, confirme
          directement ;
        </li>
        <li>
          la réservation est <strong>ferme</strong> une fois acceptée par l'Hôte (le cas échéant) et le paiement
          autorisé. Un courriel de confirmation est alors envoyé.
        </li>
      </ol>
    </LegalSection>

    <LegalSection id="paiement" title="4. Paiement">
      <p>
        Le paiement s'effectue en ligne par carte bancaire via notre prestataire de paiement{' '}
        <strong>Stripe</strong>. ZouriteBnb n'a jamais accès aux données complètes de votre carte, traitées de
        manière sécurisée par Stripe.
      </p>
      <p>
        ZouriteBnb encaisse le montant total au nom et pour le compte de l'Hôte, puis reverse à ce dernier le
        prix du séjour, déduction faite des frais de service. Le don solidaire éventuel est affecté au projet
        bénéficiaire.
      </p>
    </LegalSection>

    <LegalSection id="annulation" title="5. Annulation et remboursement">
      <p>
        <strong>Annulation par l'Hôte.</strong> Si l'Hôte annule une réservation confirmée, le Voyageur est{' '}
        <strong>intégralement remboursé</strong> des sommes versées (prix du séjour, frais de service et don
        éventuel). Le remboursement est effectué via Stripe sur le moyen de paiement d'origine.
      </p>
      <p>
        <strong>Annulation par le Voyageur.</strong> Les conditions d'annulation à l'initiative du Voyageur sont
        celles indiquées sur l'annonce et acceptées lors de la réservation. À défaut de mention contraire, les
        frais de service ne sont pas remboursables une fois la réservation confirmée.
      </p>
      <p>
        Les délais de remboursement dépendent de Stripe et de l'établissement bancaire du Voyageur.
      </p>
    </LegalSection>

    <LegalSection id="modification" title="6. Modification d'une réservation">
      <p>
        Une demande de modification (dates, durée) peut être proposée via la Plateforme. Elle ne prend effet
        qu'après acceptation de l'autre partie. Tout ajustement de prix en résultant est recalculé et affiché
        avant validation.
      </p>
    </LegalSection>

    <LegalSection id="retractation" title="7. Droit de rétractation">
      <p>
        Les prestations d'hébergement fournies à une date ou selon une périodicité déterminée sont, par nature,
        exclues du droit de rétractation. En conséquence, aucune rétractation n'est applicable une fois la
        réservation confirmée, sous réserve des conditions d'annulation prévues à l'article 5.
      </p>
    </LegalSection>

    <LegalSection id="obligations-hote" title="8. Obligations de l'Hôte vis-à-vis du Voyageur">
      <p>
        L'Hôte s'engage à fournir un hébergement conforme à l'annonce, à accueillir le Voyageur aux dates
        convenues et à respecter la réglementation touristique et de sécurité applicable. Toute réclamation
        relative au séjour relève de la relation entre le Voyageur et l'Hôte ; ZouriteBnb peut faciliter le
        dialogue sans s'y substituer.
      </p>
    </LegalSection>

    <LegalSection id="responsabilite" title="9. Responsabilité de ZouriteBnb">
      <p>
        En sa qualité d'intermédiaire, ZouriteBnb n'est pas responsable de l'exécution de la prestation
        d'hébergement, ni des litiges relatifs à son déroulement. La responsabilité de ZouriteBnb se limite au
        bon fonctionnement du service de mise en relation et d'encaissement.
      </p>
    </LegalSection>

    <LegalSection id="reclamations" title="10. Réclamations et litiges">
      <p>
        Toute réclamation peut être adressée à{' '}
        <a className="text-primary-600 hover:underline" href="mailto:contact@zouritebnb.com">
          contact@zouritebnb.com
        </a>
        . Les parties privilégieront une résolution amiable. À défaut d'accord, les présentes CGV sont régies par
        le <strong>droit mauricien</strong> et tout litige relève de la compétence exclusive des tribunaux
        compétents de la République de Maurice.
      </p>
    </LegalSection>
  </LegalPageLayout>
);

export default TermsOfSalePage;
