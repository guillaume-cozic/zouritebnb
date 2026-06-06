<?php

declare(strict_types=1);

namespace App\Tests\E2e;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Provider-side contract testing.
 *
 * Asserts that the responses produced by the API at runtime still honor the
 * published OpenAPI contract (openapi.json) — the very artifact the frontend
 * generates its TypeScript types from. If the implementation drifts from the
 * documented schema, the test fails until openapi.json is regenerated
 * (`bin/console api:openapi:export --output=openapi.json`).
 *
 * Validation is done with opis/json-schema, which implements JSON Schema
 * draft 2020-12 — the dialect used by OpenAPI 3.1 (union types such as
 * `["string", "null"]`, etc.).
 */
trait AssertsOpenApiContract
{
    private const string SPEC_URI = 'internal:///openapi.json';

    private static ?Validator $openApiValidator = null;

    private static ?object $openApiSpec = null;

    /**
     * @param string $method      HTTP verb of the operation (GET, POST...)
     * @param string $uriTemplate templated path as documented in OpenAPI,
     *                            e.g. '/api/accommodations/{id}' — NOT the concrete URI
     */
    protected function assertResponseMatchesOpenApiContract(
        ResponseInterface $response,
        string $method,
        string $uriTemplate,
    ): void {
        $status = (string) $response->getStatusCode();
        $contentType = $this->responseContentType($response);
        $schemaUri = $this->resolveResponseSchemaUri($uriTemplate, strtolower($method), $status, $contentType);

        $body = $response->getContent(false);

        // Responses without a documented body schema (e.g. 204) have nothing to validate.
        if (null === $schemaUri) {
            self::assertSame('', trim($body), \sprintf(
                'The OpenAPI contract documents no body for %s %s (%s), but the response had one.',
                strtoupper($method),
                $uriTemplate,
                $status,
            ));

            return;
        }

        $data = json_decode($body);

        $result = self::openApiValidator()->validate($data, (object) ['$ref' => $schemaUri]);

        if (!$result->isValid()) {
            $errors = json_encode(
                (new ErrorFormatter())->format($result->error()),
                \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES,
            );

            self::fail(\sprintf(
                "Response for %s %s (%s) does not match the OpenAPI contract:\n%s",
                strtoupper($method),
                $uriTemplate,
                $status,
                $errors,
            ));
        }

        $this->addToAssertionCount(1);
    }

    /**
     * Resolves the opis schema URI for a given response, or null when the
     * contract documents no body for it.
     */
    private function resolveResponseSchemaUri(string $uriTemplate, string $method, string $status, string $contentType): ?string
    {
        $spec = self::openApiSpec();

        $operation = $spec->paths->{$uriTemplate}->{$method}
            ?? self::fail(\sprintf('No OpenAPI operation documented for %s %s.', strtoupper($method), $uriTemplate));

        $responses = $operation->responses ?? null;
        if (null === $responses || !isset($responses->{$status})) {
            self::fail(\sprintf('The OpenAPI contract documents no "%s" response for %s %s.', $status, strtoupper($method), $uriTemplate));
        }

        $content = $responses->{$status}->content ?? null;
        if (null === $content || !isset($content->{$contentType})) {
            return null;
        }

        $schema = $content->{$contentType}->schema;

        // A bare component reference points to a brace-free URI we can target directly.
        if (isset($schema->{'$ref'}) && \is_string($schema->{'$ref'})) {
            return self::SPEC_URI.$schema->{'$ref'};
        }

        // Inline schema: target it through its JSON pointer inside the registered document.
        $pointer = '/paths/'.self::escapePointerToken($uriTemplate)
            .'/'.$method
            .'/responses/'.self::escapePointerToken($status)
            .'/content/'.self::escapePointerToken($contentType)
            .'/schema';

        return self::SPEC_URI.'#'.$pointer;
    }

    private function responseContentType(ResponseInterface $response): string
    {
        $header = $response->getHeaders(false)['content-type'][0] ?? '';

        // Strip parameters such as "; charset=utf-8".
        return trim(explode(';', $header)[0]);
    }

    private static function escapePointerToken(string $token): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $token);
    }

    private static function openApiValidator(): Validator
    {
        if (null === self::$openApiValidator) {
            self::$openApiValidator = new Validator();
            self::$openApiValidator->resolver()->registerRaw(self::openApiSpec(), self::SPEC_URI);
        }

        return self::$openApiValidator;
    }

    private static function openApiSpec(): object
    {
        if (null === self::$openApiSpec) {
            $path = self::getContainer()->getParameter('kernel.project_dir').'/openapi.json';
            self::$openApiSpec = json_decode((string) file_get_contents($path), false, 512, \JSON_THROW_ON_ERROR);
        }

        return self::$openApiSpec;
    }
}
