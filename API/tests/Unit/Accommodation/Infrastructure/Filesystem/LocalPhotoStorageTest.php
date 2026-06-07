<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure\Filesystem;

use App\Accommodation\Infrastructure\Filesystem\LocalPhotoStorage;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

final class LocalPhotoStorageTest extends TestCase
{
    private string $uploadDir;
    private LocalPhotoStorage $storage;

    #[Before]
    public function initStorage(): void
    {
        $this->uploadDir = sys_get_temp_dir().'/local-photo-storage-'.uniqid('', true);
        $this->storage = new LocalPhotoStorage($this->uploadDir);
    }

    #[After]
    public function cleanUp(): void
    {
        if (!is_dir($this->uploadDir)) {
            return;
        }

        foreach (glob($this->uploadDir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->uploadDir);
    }

    public function test_should_create_directory_and_store_file_when_directory_missing(): void
    {
        self::assertDirectoryDoesNotExist($this->uploadDir);

        $this->storage->store('photo.webp', 'binary-content');

        self::assertDirectoryExists($this->uploadDir);
        self::assertSame('binary-content', file_get_contents($this->uploadDir.'/photo.webp'));
    }

    public function test_should_store_file_when_directory_already_exists(): void
    {
        mkdir($this->uploadDir, 0o755, true);

        $this->storage->store('photo.webp', 'content');

        self::assertSame('content', file_get_contents($this->uploadDir.'/photo.webp'));
    }

    public function test_should_delete_an_existing_file(): void
    {
        $this->storage->store('photo.webp', 'content');
        self::assertFileExists($this->uploadDir.'/photo.webp');

        $this->storage->delete('photo.webp');

        self::assertFileDoesNotExist($this->uploadDir.'/photo.webp');
    }

    public function test_should_do_nothing_when_deleting_a_missing_file(): void
    {
        mkdir($this->uploadDir, 0o755, true);

        $this->storage->delete('does-not-exist.webp');

        self::assertFileDoesNotExist($this->uploadDir.'/does-not-exist.webp');
    }
}
