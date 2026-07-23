<?php

declare(strict_types=1);

namespace App\Tests\E2e\Contact;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Contact\Infrastructure\Doctrine\ContactMessageEntity;
use Doctrine\ORM\EntityManagerInterface;

abstract class ContactApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    /**
     * Reads back the persisted contact message for an email address, fresh from the
     * database (the identity map is cleared first so we observe the request's side effects).
     */
    protected function findContactMessage(string $email): ?ContactMessageEntity
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        return $em->getRepository(ContactMessageEntity::class)
            ->findOneBy(['email' => $email]);
    }

    /**
     * Counts the persisted contact messages, fresh from the database.
     */
    protected function countContactMessages(): int
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();

        return $em->getRepository(ContactMessageEntity::class)->count([]);
    }
}
