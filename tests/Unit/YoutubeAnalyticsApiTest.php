<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit;

use Google\Service\YouTubeAnalytics;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Tests\TestCase;
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeClient;

class YoutubeAnalyticsApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        YoutubeAnalyticsApi::reset();
    }

    public function tearDown(): void
    {
        YoutubeAnalyticsApi::reset();
        parent::tearDown();
    }

    public function test_fake_returns_client_instance(): void
    {
        $client = YoutubeAnalyticsApi::fake();

        $this->assertInstanceOf(YoutubeClient::class, $client);
    }

    public function test_analytics_returns_service(): void
    {
        YoutubeAnalyticsApi::fake();

        $analytics = YoutubeAnalyticsApi::analytics();

        $this->assertInstanceOf(YouTubeAnalytics::class, $analytics);
    }

    public function test_fake_with_responses(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
            AnalyticsQueryResponse::dailyMetrics(),
        ]);

        $this->assertInstanceOf(YoutubeClient::class, YoutubeAnalyticsApi::instance());
    }

    public function test_assert_sent_count(): void
    {
        $client = YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
        ]);

        // Make a fake request by accessing the mock handler
        $client->queue(AnalyticsQueryResponse::dailyMetrics());

        YoutubeAnalyticsApi::assertSentCount(0); // No actual HTTP requests made yet
    }

    public function test_assert_nothing_sent(): void
    {
        YoutubeAnalyticsApi::fake();

        YoutubeAnalyticsApi::assertNothingSent();
        
        // Should not throw
        $this->assertTrue(true);
    }

    public function test_container_swap_callback(): void
    {
        $called = false;

        YoutubeAnalyticsApi::registerContainerSwap(function () use (&$called) {
            $called = true;
        });

        YoutubeAnalyticsApi::fake();

        $this->assertTrue($called);
    }

    public function test_instance_returns_null_when_not_initialized(): void
    {
        $this->assertNull(YoutubeAnalyticsApi::instance());
    }

    public function test_throws_runtime_exception_when_not_initialized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('YoutubeAnalyticsApi has not been initialized');

        YoutubeAnalyticsApi::assertNothingSent();
    }
}