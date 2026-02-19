<?php

declare(strict_types=1);

namespace Viewtrender\Youtube;

use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Viewtrender\Youtube\Exceptions\StrayRequestException;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeClient
{
    private MockHandler $mockHandler;

    private HandlerStack $handlerStack;

    private GuzzleClient $httpClient;

    private GoogleClient $googleClient;

    private RequestHistory $requestHistory;

    private bool $preventStrayRequests = false;

    /**
     * @param  array<ResponseInterface|FakeResponse>  $responses
     */
    public function __construct(array $responses = [])
    {
        $this->mockHandler = new MockHandler();
        $this->requestHistory = new RequestHistory();

        $historyContainer = &$this->requestHistory->getContainer();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($historyContainer));

        $this->httpClient = new GuzzleClient(['handler' => $this->handlerStack]);

        $this->googleClient = new GoogleClient();
        $this->googleClient->setHttpClient($this->httpClient);
        $this->googleClient->setAccessToken([
            'access_token' => 'fake-access-token',
            'expires_in' => 3600,
            'created' => time(),
        ]);

        foreach ($responses as $response) {
            $this->queue($response);
        }
    }

    public function queue(ResponseInterface|FakeResponse $response): self
    {
        if ($response instanceof FakeResponse) {
            $response = $response->toPsrResponse();
        }

        $this->mockHandler->append($response);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function queueJson(array $body, int $status = 200): self
    {
        return $this->queue(FakeResponse::make($body)->status($status));
    }

    public function preventStrayRequests(): self
    {
        $this->preventStrayRequests = true;

        $self = $this;
        $this->mockHandler->append(function (RequestInterface $request) use ($self) {
            if ($self->preventStrayRequests) {
                throw new StrayRequestException($request);
            }
        });

        return $this;
    }

    public function getGoogleClient(): GoogleClient
    {
        return $this->googleClient;
    }

    public function getRequestHistory(): RequestHistory
    {
        return $this->requestHistory;
    }

    public function getMockHandler(): MockHandler
    {
        return $this->mockHandler;
    }
}
