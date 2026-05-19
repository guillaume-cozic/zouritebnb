<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Conversation\Infrastructure\Doctrine\ConversationEntity;
use App\Conversation\Infrastructure\Doctrine\MessageEntity;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Uid\Uuid;

class ReservationConversationFixtures extends Fixture implements DependentFixtureInterface
{
    private const string HOST_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    private const array GUESTS = [
        ['firstName' => 'Camille', 'lastName' => 'Martin', 'email' => 'camille.martin@example.test'],
        ['firstName' => 'Mehdi', 'lastName' => 'Bensaïd', 'email' => 'mehdi.bensaid@example.test'],
        ['firstName' => 'Sophie', 'lastName' => 'Dubois', 'email' => 'sophie.dubois@example.test'],
        ['firstName' => 'Thomas', 'lastName' => 'Garcia', 'email' => 'thomas.garcia@example.test'],
        ['firstName' => 'Léa', 'lastName' => 'Rousseau', 'email' => 'lea.rousseau@example.test'],
    ];

    private const array GUEST_NOTES = [
        'Bonjour, nous voyageons avec un bébé de 8 mois. Possibilité d\'avoir un lit parapluie ?',
        'Bonjour, est-il possible d\'arriver un peu plus tôt vers 13h ?',
        null,
        'Nous sommes intéressés par votre logement pour fêter notre anniversaire de mariage.',
        null,
        'Avez-vous des recommandations de restaurants à proximité ?',
        'Le logement est-il accessible en transport public depuis l\'aéroport ?',
        null,
    ];

    private const array GUEST_MESSAGES = [
        'Merci pour votre réponse rapide !',
        'Parfait, ça nous convient.',
        'Le logement accepte-t-il les animaux ? Nous avons un petit chien très calme.',
        'Pouvez-vous confirmer l\'heure d\'arrivée ?',
        'Y a-t-il un parking sur place ?',
        'Le wifi est-il rapide ? Nous devons un peu télétravailler.',
        'Super, à très bientôt !',
        'Nous arriverons vers 16h, est-ce que ça vous convient ?',
        'Y a-t-il une supérette à proximité ?',
        'Nous avons hâte de découvrir Rodrigues !',
    ];

    private const array HOST_MESSAGES = [
        'Bonjour, bienvenue ! Je vous confirme la disponibilité aux dates demandées.',
        'Bonjour, oui nous pouvons aménager l\'arrivée plus tôt sans problème.',
        'Pour les animaux, c\'est ok si le chien est calme. Petit supplément ménage de 30€.',
        'Le wifi est en fibre, vous pourrez télétravailler sans souci.',
        'Le check-in est libre, je vous enverrai le code de la porte la veille.',
        'Nous recommandons "Le St Pierre" pour le poisson grillé, à 5 min à pied.',
        'Il y a un parking privatif pour 2 voitures devant le logement.',
        'Hâte de vous accueillir, bon voyage !',
        'L\'aéroport est à 20 min en taxi, comptez environ 1500 MUR.',
        'Vous trouverez tout le nécessaire dans le placard, draps, serviettes, produits d\'entretien.',
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $teamId = Uuid::fromString(self::HOST_TEAM_UUID);

        // 1. Create or reuse host user (team member)
        $hostUser = $manager->getRepository(UserEntity::class)->findOneBy(['teamId' => $teamId]);
        if (null === $hostUser) {
            $hostUser = (new UserEntity())
                ->setId(Uuid::v7())
                ->setEmail('host@example.test')
                ->setHashedPassword('$2y$13$FqeLc3UT09FlKQVOnHCAIOF443jByg.bYhrJ8Zrb2ENpbBdzunN1.') // "password"
                ->setTeamId($teamId)
                ->setFirstName('Marie')
                ->setLastName('Hôte');
            $manager->persist($hostUser);
        }

        // 2. Create guest users on their own teams
        $guestUsers = [];
        foreach (self::GUESTS as $g) {
            $existing = $manager->getRepository(UserEntity::class)->findOneBy(['email' => $g['email']]);
            if (null !== $existing) {
                $guestUsers[] = $existing;
                continue;
            }
            $guest = (new UserEntity())
                ->setId(Uuid::v7())
                ->setEmail($g['email'])
                ->setHashedPassword('$2y$13$FqeLc3UT09FlKQVOnHCAIOF443jByg.bYhrJ8Zrb2ENpbBdzunN1.') // "password"
                ->setTeamId(Uuid::v7())
                ->setFirstName($g['firstName'])
                ->setLastName($g['lastName']);
            $manager->persist($guest);
            $guestUsers[] = $guest;
        }
        $manager->flush();

        // 3. Get all accommodations belonging to the host team
        $accommodations = $manager->getRepository(AccommodationEntity::class)->findBy(['teamId' => $teamId]);
        if ([] === $accommodations) {
            return;
        }

        // 4. For each accommodation, create 2-4 reservations + conversations
        foreach ($accommodations as $accommodation) {
            $count = $faker->numberBetween(2, 4);
            for ($i = 0; $i < $count; ++$i) {
                /** @var UserEntity $guest */
                $guest = $faker->randomElement($guestUsers);

                // Random check-in date in the next 90 days
                $daysFromNow = $faker->numberBetween(7, 90);
                $checkIn = (new \DateTimeImmutable())->modify("+{$daysFromNow} days")->setTime(15, 0);
                $nights = $faker->numberBetween(2, 10);
                $checkOut = $checkIn->modify("+{$nights} days")->setTime(11, 0);

                $pricePerNight = (float) $accommodation->getPrice();
                $totalPrice = $pricePerNight * $nights;

                $status = $faker->randomElement(['pending', 'pending', 'confirmed', 'confirmed', 'refused', 'cancelled']);
                $note = $faker->randomElement(self::GUEST_NOTES);
                $guestName = $guest->getFirstName().' '.$guest->getLastName();

                $reservationId = Uuid::v7();
                $reservation = (new ReservationEntity())
                    ->setId($reservationId)
                    ->setAccommodationId($accommodation->getId())
                    ->setTeamId($teamId)
                    ->setGuestUserId($guest->getId())
                    ->setCheckIn($checkIn)
                    ->setCheckOut($checkOut)
                    ->setGuestName($guestName)
                    ->setStatus($status)
                    ->setTotalPrice($totalPrice)
                    ->setPricePerNight($pricePerNight)
                    ->setAppliedDiscountPercentage(null);
                $manager->persist($reservation);

                // Build conversation: created at request time (a bit before checkIn)
                $createdAt = $checkIn->modify('-'.$faker->numberBetween(1, 30).' days')->setTime($faker->numberBetween(8, 20), $faker->numberBetween(0, 59));

                $conversation = (new ConversationEntity())
                    ->setId(Uuid::v7())
                    ->setReservationId($reservationId)
                    ->setAccommodationId($accommodation->getId())
                    ->setTeamId($teamId)
                    ->setGuestUserId($guest->getId())
                    ->setCreatedAt($createdAt);

                // Initial system message (template + optional note)
                $opening = \sprintf(
                    "Bonjour, je m'appelle %s et je souhaite réserver votre hébergement du %s au %s.",
                    $guestName,
                    $checkIn->format('d/m/Y'),
                    $checkOut->format('d/m/Y'),
                );
                if (null !== $note) {
                    $opening .= "\n\n".$note;
                }

                $conversation->addMessage((new MessageEntity())
                    ->setId(Uuid::v7())
                    ->setBody($opening)
                    ->setAuthorUserId(null)
                    ->setSentAt($createdAt)
                    ->setIsSystem(true));

                // 2-6 alternating user messages, starting with host
                $messageCount = $faker->numberBetween(2, 6);
                $lastSentAt = $createdAt;
                for ($j = 0; $j < $messageCount; ++$j) {
                    $lastSentAt = $lastSentAt->modify('+'.$faker->numberBetween(10, 240).' minutes');
                    $isHost = 0 === $j % 2;
                    $body = $isHost
                        ? $faker->randomElement(self::HOST_MESSAGES)
                        : $faker->randomElement(self::GUEST_MESSAGES);

                    $conversation->addMessage((new MessageEntity())
                        ->setId(Uuid::v7())
                        ->setBody($body)
                        ->setAuthorUserId($isHost ? $hostUser->getId() : $guest->getId())
                        ->setSentAt($lastSentAt)
                        ->setIsSystem(false));
                }

                // Trailing system message for refused / cancelled
                if ('refused' === $status) {
                    $conversation->addMessage((new MessageEntity())
                        ->setId(Uuid::v7())
                        ->setBody("L'hôte a refusé cette demande de réservation.")
                        ->setAuthorUserId(null)
                        ->setSentAt($lastSentAt->modify('+15 minutes'))
                        ->setIsSystem(true));
                } elseif ('cancelled' === $status) {
                    $conversation->addMessage((new MessageEntity())
                        ->setId(Uuid::v7())
                        ->setBody('La réservation a été annulée.')
                        ->setAuthorUserId(null)
                        ->setSentAt($lastSentAt->modify('+15 minutes'))
                        ->setIsSystem(true));
                }

                $manager->persist($conversation);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AccommodationFixtures::class];
    }
}
