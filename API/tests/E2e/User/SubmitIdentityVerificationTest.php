<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SubmitIdentityVerificationTest extends UserApiTestCase
{
    public function test_should_verify_identity_and_return_status(): void
    {
        $id = $this->insertUser(email: 'host@example.com');
        $headers = $this->authHeaders('host@example.com');

        self::createClient()->request('POST', '/api/users/'.$id.'/identity-verification', [
            'headers' => $headers + ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['documentType' => 'passport'],
                'files' => [
                    'document' => $this->image(),
                    'selfie' => $this->image(),
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'status' => 'verified',
            'documentType' => 'passport',
        ]);
    }

    public function test_should_expose_verification_status_via_get(): void
    {
        $id = $this->insertUser(email: 'host@example.com');
        $headers = $this->authHeaders('host@example.com');

        $response = self::createClient()->request('GET', '/api/users/'.$id.'/identity-verification', [
            'headers' => $headers,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('not_started', $response->toArray()['status']);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertUser(email: 'host@example.com');

        self::createClient()->request('POST', '/api/users/'.$id.'/identity-verification', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['documentType' => 'passport'],
                'files' => [
                    'document' => $this->image(),
                    'selfie' => $this->image(),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_422_with_invalid_document_type(): void
    {
        $id = $this->insertUser(email: 'host@example.com');
        $headers = $this->authHeaders('host@example.com');

        self::createClient()->request('POST', '/api/users/'.$id.'/identity-verification', [
            'headers' => $headers + ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['documentType' => 'unknown'],
                'files' => [
                    'document' => $this->image(),
                    'selfie' => $this->image(),
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    private function image(): UploadedFile
    {
        $resource = imagecreatetruecolor(1, 1);
        $tmpFile = tempnam(sys_get_temp_dir(), 'identity_test_');
        imagejpeg($resource, $tmpFile);

        return new UploadedFile($tmpFile, 'doc.jpg', 'image/jpeg', test: true);
    }
}
