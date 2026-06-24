<?php

declare(strict_types=1);

namespace App\User\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\User\Domain\Command\UploadHostAvatarCommand;
use App\User\Domain\Exception\InvalidAvatarException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Port\AvatarStorage;
use App\User\Domain\Port\UserRepository;

final readonly class UploadHostAvatar
{
    /** @var array<string, string> MIME type => file extension */
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Hard cap on the uploaded image size (10 MB) to prevent memory-exhaustion DoS. */
    private const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private UserRepository $repository,
        private AvatarStorage $storage,
    ) {
    }

    /**
     * Stores the uploaded avatar, attaches it to the user, and returns the generated filename.
     */
    public function handle(UploadHostAvatarCommand $command): string
    {
        if (!isset(self::ALLOWED_MIME_TYPES[$command->mimeType])) {
            throw InvalidAvatarException::becauseInvalidMimeType($command->mimeType);
        }

        if ($command->size > self::MAX_SIZE_BYTES) {
            throw InvalidAvatarException::becauseTooLarge($command->size, self::MAX_SIZE_BYTES);
        }

        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw UserNotFoundException::becauseNotFound($command->userId->toRfc4122());
        }

        $filename = UuidGenerator::generate()->toRfc4122().'.'.self::ALLOWED_MIME_TYPES[$command->mimeType];
        $this->storage->store($filename, $command->content);

        $previous = $user->getAvatarFilename();
        $user->changeAvatar($filename);
        $this->repository->save($user);

        if (null !== $previous) {
            $this->storage->delete($previous);
        }

        return $filename;
    }
}
