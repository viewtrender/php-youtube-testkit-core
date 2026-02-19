<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Viewtrender\Youtube\Responses\ErrorResponse;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class FakeResponseTest extends TestCase
{
    public function test_make_with_array_body(): void
    {
        $response = FakeResponse::make(['foo' => 'bar']);

        $this->assertSame('{"foo":"bar"}', $response->body);
        $this->assertSame(200, $response->statusCode);
    }

    public function test_make_with_string_body(): void
    {
        $response = FakeResponse::make('{"raw":"json"}');

        $this->assertSame('{"raw":"json"}', $response->body);
    }

    public function test_status_setter(): void
    {
        $response = FakeResponse::make()->status(404);

        $this->assertSame(404, $response->statusCode);
    }

    public function test_to_psr_response(): void
    {
        $psr = FakeResponse::make(['test' => true])->status(201)->toPsrResponse();

        $this->assertInstanceOf(ResponseInterface::class, $psr);
        $this->assertSame(201, $psr->getStatusCode());
        $this->assertStringContainsString('application/json', $psr->getHeaderLine('Content-Type'));
        $this->assertSame('{"test":true}', (string) $psr->getBody());
    }

    public function test_from_fixture(): void
    {
        $response = FakeResponse::fromFixture('youtube/videos-list.json');
        $body = json_decode($response->body, true);

        $this->assertSame('youtube#videoListResponse', $body['kind']);
    }

    public function test_from_fixture_throws_on_missing_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fixture file not found');

        FakeResponse::fromFixture('nonexistent.json');
    }

    public function test_custom_header(): void
    {
        $psr = FakeResponse::make()->header('X-Custom', 'value')->toPsrResponse();

        $this->assertSame('value', $psr->getHeaderLine('X-Custom'));
    }

    /**
     * @throws JsonException
     */
    public function test_body_setter(): void
    {
        $response = FakeResponse::make(['original' => true])->setBody(['replaced' => true]);

        $this->assertSame('{"replaced":true}', $response->body);
    }

    public function test_error_not_found(): void
    {
        $response = ErrorResponse::notFound();
        $body = json_decode($response->body, true);

        $this->assertSame(404, $response->statusCode);
        $this->assertSame(404, $body['error']['code']);
        $this->assertSame('NOT_FOUND', $body['error']['status']);
    }

    public function test_error_forbidden(): void
    {
        $response = ErrorResponse::forbidden();

        $this->assertSame(403, $response->statusCode);
    }

    public function test_error_unauthorized(): void
    {
        $response = ErrorResponse::unauthorized();

        $this->assertSame(401, $response->statusCode);
    }

    public function test_error_quota_exceeded(): void
    {
        $response = ErrorResponse::quotaExceeded();
        $body = json_decode($response->body, true);

        $this->assertSame(403, $response->statusCode);
        $this->assertSame('rateLimitExceeded', $body['error']['errors'][0]['reason']);
    }

    public function test_error_bad_request(): void
    {
        $response = ErrorResponse::badRequest();

        $this->assertSame(400, $response->statusCode);
    }
}
