<?php

declare(strict_types=1);

namespace App\Tests\E2e\Shared;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use PHPUnit\Framework\Attributes\After;

final class ServePhotoControllerTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private const string FILENAME = 'e2e-serve-photo-test.webp';

    #[After]
    public function removeUploadedFile(): void
    {
        @unlink($this->uploadDir().'/'.self::FILENAME);
    }

    public function test_should_serve_photo_with_immutable_cache_headers(): void
    {
        $client = self::createClient();

        $dir = $this->uploadDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }
        file_put_contents($dir.'/'.self::FILENAME, 'webp-bytes');

        $response = $client->request('GET', '/uploads/photos/'.self::FILENAME);

        self::assertResponseStatusCodeSame(200);
        $cacheControl = implode(', ', $response->getHeaders(false)['cache-control'] ?? []);
        self::assertStringContainsString('public', $cacheControl);
        self::assertStringContainsString('max-age=31536000', $cacheControl);
        self::assertStringContainsString('immutable', $cacheControl);
    }

    public function test_should_return_404_for_missing_file(): void
    {
        self::createClient()->request('GET', '/uploads/photos/does-not-exist.webp');

        self::assertResponseStatusCodeSame(404);
    }

    private function uploadDir(): string
    {
        /** @var string $projectDir */
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');

        return $projectDir.'/var/uploads/photos';
    }
}
