<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Integration;

use Google\Service\YouTubeAnalytics;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Tests\TestCase;
use Viewtrender\Youtube\YoutubeAnalyticsApi;

class YoutubeAnalyticsIntegrationTest extends TestCase
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

    /**
     * Test that demonstrates how to use the Analytics API mocking
     * in a real scenario, similar to ViewTrender app usage
     */
    public function test_youtube_analytics_service_mocking_scenario(): void
    {
        // Set up fake responses for various Analytics API calls
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(),
            AnalyticsQueryResponse::dailyMetrics([
                'rows' => [
                    ['2024-01-01', 50000, 25000, 100, 5],
                    ['2024-01-02', 52000, 26000, 105, 4],
                ]
            ]),
            AnalyticsQueryResponse::topVideos(),
            AnalyticsQueryResponse::trafficSources(),
            AnalyticsQueryResponse::demographics(),
            AnalyticsQueryResponse::geography(),
            AnalyticsQueryResponse::deviceTypes(),
            AnalyticsQueryResponse::videoAnalytics(),
            AnalyticsQueryResponse::videoTypes(),
        ]);

        // Get the Analytics service instance 
        $analytics = YoutubeAnalyticsApi::analytics();

        // Verify it's the correct type
        $this->assertInstanceOf(YouTubeAnalytics::class, $analytics);

        // This is where you would normally make Analytics API calls
        // In a real test, the YouTubeAnalyticsService would use this $analytics instance
        // and get back the mocked responses we set up above

        // Verify the fake was set up correctly
        $this->assertNotNull(YoutubeAnalyticsApi::instance());
    }

    /**
     * Test the assertion helpers for Analytics API calls
     */
    public function test_analytics_assertion_helpers(): void
    {
        $client = YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::dailyMetrics(),
        ]);

        // Test that we can assert nothing was sent initially  
        YoutubeAnalyticsApi::assertNothingSent();

        // Test custom assertion methods
        $this->assertTrue(method_exists(YoutubeAnalyticsApi::class, 'assertQueriedAnalytics'));
        $this->assertTrue(method_exists(YoutubeAnalyticsApi::class, 'assertQueriedMetrics'));
        $this->assertTrue(method_exists(YoutubeAnalyticsApi::class, 'assertQueriedDimensions'));
    }

    /**
     * Test that responses can be customized for specific test scenarios
     */
    public function test_custom_response_overrides(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview([
                'rows' => [
                    [999999, 500000, 250, 100, 5, 15000, 200, 800, 2500] // Custom metrics
                ]
            ])
        ]);

        $analytics = YoutubeAnalyticsApi::analytics();
        $this->assertInstanceOf(YouTubeAnalytics::class, $analytics);
    }

    /**
     * Test empty/error responses
     */
    public function test_empty_analytics_response(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::make(
                [
                    ['name' => 'views', 'columnType' => 'METRIC', 'dataType' => 'INTEGER']
                ],
                [] // Empty rows
            )
        ]);

        $analytics = YoutubeAnalyticsApi::analytics();
        $this->assertInstanceOf(YouTubeAnalytics::class, $analytics);
    }
}