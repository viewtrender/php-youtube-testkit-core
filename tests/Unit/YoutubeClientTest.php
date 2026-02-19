<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit;

use Google\Client as GoogleClient;
use Viewtrender\Youtube\YoutubeClient;
use Viewtrender\Youtube\RequestHistory;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeClientTest extends TestCase
{
    public function test_creates_google_client(): void
    {
        $fake = new YoutubeClient();

        $this->assertInstanceOf(GoogleClient::class, $fake->getGoogleClient());
    }

    public function test_google_client_has_access_token(): void
    {
        $fake = new YoutubeClient();
        $token = $fake->getGoogleClient()->getAccessToken();

        $this->assertSame('fake-access-token', $token['access_token']);
    }

    public function test_returns_request_history(): void
    {
        $fake = new YoutubeClient();

        $this->assertInstanceOf(RequestHistory::class, $fake->getRequestHistory());
    }

    public function test_queues_psr7_response(): void
    {
        $response = FakeResponse::make(['test' => true])->toPsrResponse();
        $fake = new YoutubeClient([$response]);

        $this->assertNotNull($fake->getMockHandler());
    }

    public function test_queues_fake_response(): void
    {
        $response = FakeResponse::make(['test' => true]);
        $fake = new YoutubeClient([$response]);

        $this->assertNotNull($fake->getMockHandler());
    }

    public function test_queue_json_creates_response(): void
    {
        $fake = new YoutubeClient();
        $result = $fake->queueJson(['foo' => 'bar']);

        $this->assertSame($fake, $result);
    }

    public function test_fluent_queue(): void
    {
        $fake = new YoutubeClient();
        $result = $fake->queue(FakeResponse::make(['test' => true]));

        $this->assertSame($fake, $result);
    }
}
