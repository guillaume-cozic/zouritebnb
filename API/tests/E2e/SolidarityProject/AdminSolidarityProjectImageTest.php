<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

use App\Tests\E2e\AuthenticatedClientTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AdminSolidarityProjectImageTest extends SolidarityProjectApiTestCase
{
    use AuthenticatedClientTrait;

    public function test_should_upload_an_image_and_serve_it_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $file = new UploadedFile($this->createTempImage(), 'project.jpg', 'image/jpeg', test: true);

        $response = self::createClient()->request('POST', '/api/admin/solidarity-project-images', [
            'headers' => $this->authHeaders('admin@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => ['files' => ['file' => $file]],
        ]);

        self::assertResponseStatusCodeSame(201);
        $imageUrl = $response->toArray()['imageUrl'];
        self::assertStringContainsString('/uploads/solidarity-projects/', $imageUrl);

        // The freshly uploaded image is served publicly.
        $path = parse_url($imageUrl, \PHP_URL_PATH);
        self::createClient()->request('GET', $path);
        self::assertResponseIsSuccessful();
    }

    public function test_should_reject_a_non_image_file(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $tmp = tempnam(sys_get_temp_dir(), 'sp_img_');
        file_put_contents($tmp, 'not an image');
        $file = new UploadedFile($tmp, 'note.txt', 'text/plain', test: true);

        self::createClient()->request('POST', '/api/admin/solidarity-project-images', [
            'headers' => $this->authHeaders('admin@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => ['files' => ['file' => $file]],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');
        $file = new UploadedFile($this->createTempImage(), 'project.jpg', 'image/jpeg', test: true);

        self::createClient()->request('POST', '/api/admin/solidarity-project-images', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'multipart/form-data'],
            'extra' => ['files' => ['file' => $file]],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        $file = new UploadedFile($this->createTempImage(), 'project.jpg', 'image/jpeg', test: true);

        self::createClient()->request('POST', '/api/admin/solidarity-project-images', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => ['files' => ['file' => $file]],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    private function createTempImage(): string
    {
        $image = imagecreatetruecolor(1, 1);
        $tmpFile = tempnam(sys_get_temp_dir(), 'sp_img_');
        imagejpeg($image, $tmpFile);

        return $tmpFile;
    }
}
