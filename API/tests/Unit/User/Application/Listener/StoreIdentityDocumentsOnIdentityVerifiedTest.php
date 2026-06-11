<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Listener;

use App\Tests\Unit\User\Infrastructure\InMemoryIdentityDocumentStorage;
use App\User\Application\Listener\StoreIdentityDocumentsOnIdentityVerified;
use App\User\Domain\Event\IdentityVerified;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class StoreIdentityDocumentsOnIdentityVerifiedTest extends TestCase
{
    public function test_should_store_both_files(): void
    {
        $storage = new InMemoryIdentityDocumentStorage();
        $listener = new StoreIdentityDocumentsOnIdentityVerified($storage);

        $listener(new IdentityVerified(
            userId: Uuid::v7(),
            documentFilename: 'doc.jpg',
            documentContent: 'doc-bytes',
            selfieFilename: 'selfie.png',
            selfieContent: 'selfie-bytes',
        ));

        self::assertSame('doc-bytes', $storage->stored['doc.jpg']);
        self::assertSame('selfie-bytes', $storage->stored['selfie.png']);
    }
}
