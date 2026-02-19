<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Responses;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class FakeResponse
{
    public int $statusCode = 200;

    /** @var array<string, string> */
    private array $headers = [
        'Content-Type' => 'application/json; charset=UTF-8',
    ];

    public string $body;

    private function __construct(string|array $body)
    {
        $this->body = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
    }

    public static function make(string|array $body = []): self
    {
        return new self($body);
    }

    public static function fromFixture(string $path): self
    {
        $fullPath = dirname(__DIR__) . '/Fixtures/' . ltrim($path, '/');

        if (! file_exists($fullPath)) {
            throw new InvalidArgumentException("Fixture file not found: {$fullPath}");
        }

        return new self(file_get_contents($fullPath));
    }

    public function status(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function setBody(string|array $body): self
    {
        $this->body = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;

        return $this;
    }

    public function toPsrResponse(): ResponseInterface
    {
        return new Response(
            $this->statusCode,
            $this->headers,
            $this->body,
        );
    }

}
