<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\User\Infrastructure\InMemoryUserRepository;
use App\User\Application\UseCase\UploadHostAvatar;
use App\User\Domain\Command\UploadHostAvatarCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidAvatarException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Port\AvatarStorage;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UploadHostAvatarTest extends TestCase
{
    private InMemoryUserRepository $repository;
    /** @var AvatarStorage&object{stored: array<string, string>, deleted: list<string>} */
    private object $storage;
    private UploadHostAvatar $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->storage = new class implements AvatarStorage {
            /** @var array<string, string> */
            public array $stored = [];
            /** @var list<string> */
            public array $deleted = [];

            public function store(string $filename, string $content): void
            {
                $this->stored[$filename] = $content;
            }

            public function delete(string $filename): void
            {
                $this->deleted[] = $filename;
            }
        };
        $this->useCase = new UploadHostAvatar($this->repository, $this->storage);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_store_the_avatar_and_attach_it_to_the_user(): void
    {
        UuidGenerator::freeze(Uuid::fromString('01961e2f-dead-7000-beef-000000000001'));
        $userId = Uuid::v7();
        $this->repository->save(new User(
            id: $userId,
            email: 'host@example.com',
            hashedPassword: 'hashed',
            teamId: Uuid::v7(),
        ));

        $filename = $this->useCase->handle(new UploadHostAvatarCommand(
            userId: $userId,
            content: 'binary-bytes',
            mimeType: 'image/jpeg',
            size: 2048,
        ));

        self::assertSame('01961e2f-dead-7000-beef-000000000001.jpg', $filename);
        self::assertSame('binary-bytes', $this->storage->stored[$filename]);
        self::assertSame($filename, $this->repository->findById($userId)?->getAvatarFilename());
    }

    public function test_should_delete_the_previous_avatar_when_replaced(): void
    {
        $userId = Uuid::v7();
        $user = new User(
            id: $userId,
            email: 'host@example.com',
            hashedPassword: 'hashed',
            teamId: Uuid::v7(),
        );
        $user->changeAvatar('old-avatar.png');
        $this->repository->save($user);

        $this->useCase->handle(new UploadHostAvatarCommand(
            userId: $userId,
            content: 'x',
            mimeType: 'image/png',
            size: 10,
        ));

        self::assertContains('old-avatar.png', $this->storage->deleted);
    }

    public function test_should_reject_an_unsupported_mime_type(): void
    {
        $this->expectException(InvalidAvatarException::class);

        $this->useCase->handle(new UploadHostAvatarCommand(
            userId: Uuid::v7(),
            content: 'x',
            mimeType: 'application/pdf',
            size: 10,
        ));
    }

    public function test_should_reject_a_file_that_is_too_large(): void
    {
        $this->expectException(InvalidAvatarException::class);

        $this->useCase->handle(new UploadHostAvatarCommand(
            userId: Uuid::v7(),
            content: 'x',
            mimeType: 'image/jpeg',
            size: 11 * 1024 * 1024,
        ));
    }

    public function test_should_fail_when_the_user_does_not_exist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->useCase->handle(new UploadHostAvatarCommand(
            userId: Uuid::v7(),
            content: 'x',
            mimeType: 'image/jpeg',
            size: 10,
        ));
    }
}
