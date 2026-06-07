<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadAccommodationPhotoTest extends AccommodationApiTestCase
{
    public function test_should_upload_photo(): void
    {
        $id = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);

        $tmpFile = $this->createTempImage();
        $uploadedFile = new UploadedFile($tmpFile, 'test.jpg', 'image/jpeg', test: true);

        self::createClient()->request('POST', '/api/accommodations/'.$id.'/photos', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    public function test_should_not_upload_when_accommodation_not_found(): void
    {
        $tmpFile = $this->createTempImage();
        $uploadedFile = new UploadedFile($tmpFile, 'test.jpg', 'image/jpeg', test: true);

        self::createClient()->request('POST', '/api/accommodations/00000000-0000-0000-0000-000000000000/photos', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile],
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_not_upload_with_invalid_mime_type(): void
    {
        $id = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);

        $tmpFile = tempnam(sys_get_temp_dir(), 'photo_test_');
        file_put_contents($tmpFile, str_repeat('x', 100));
        $uploadedFile = new UploadedFile($tmpFile, 'test.txt', 'text/plain', test: true);

        self::createClient()->request('POST', '/api/accommodations/'.$id.'/photos', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_not_upload_without_file(): void
    {
        $id = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);

        self::createClient()->request('POST', '/api/accommodations/'.$id.'/photos', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
        ]);

        self::assertResponseStatusCodeSame(500);
    }

    private function createTempImage(): string
    {
        $image = imagecreatetruecolor(1, 1);
        $tmpFile = tempnam(sys_get_temp_dir(), 'photo_test_');
        imagejpeg($image, $tmpFile);

        return $tmpFile;
    }
}
