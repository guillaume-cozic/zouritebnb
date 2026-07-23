<?php

declare(strict_types=1);

namespace App\Tests\Integration\Contact\Infrastructure;

use App\Contact\Domain\Entity\ContactMessage;
use App\Contact\Domain\Port\ContactMessageRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineContactMessageRepositoryTest extends RepositoryTestCase
{
    private ContactMessageRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(ContactMessageRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $sentAt = new \DateTimeImmutable('2026-07-23 10:15:00');
        $message = new ContactMessage(
            id: $id,
            name: 'Jeanne Dupont',
            email: 'jeanne.dupont@example.com',
            subject: 'Question sur une réservation',
            message: 'Bonjour, je souhaite en savoir plus sur les modalités d’annulation.',
            sentAt: $sentAt,
        );

        $this->repository->save($message);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('Jeanne Dupont', $found->getName());
        self::assertSame('jeanne.dupont@example.com', $found->getEmail());
        self::assertSame('Question sur une réservation', $found->getSubject());
        self::assertSame('Bonjour, je souhaite en savoir plus sur les modalités d’annulation.', $found->getMessage());
        self::assertEquals($sentAt, $found->getSentAt());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $message = new ContactMessage(
            id: $id,
            name: 'Jeanne Dupont',
            email: 'jeanne.dupont@example.com',
            subject: 'Premier sujet',
            message: 'Premier message.',
            sentAt: new \DateTimeImmutable('2026-07-23 10:15:00'),
        );
        $this->repository->save($message);

        $updated = new ContactMessage(
            id: $id,
            name: 'Jeanne Durand',
            email: 'jeanne.durand@example.com',
            subject: 'Sujet mis à jour',
            message: 'Message mis à jour.',
            sentAt: new \DateTimeImmutable('2026-07-24 08:00:00'),
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame('Jeanne Durand', $found->getName());
        self::assertSame('jeanne.durand@example.com', $found->getEmail());
        self::assertSame('Sujet mis à jour', $found->getSubject());
        self::assertSame('Message mis à jour.', $found->getMessage());
        self::assertEquals(new \DateTimeImmutable('2026-07-24 08:00:00'), $found->getSentAt());
    }
}
