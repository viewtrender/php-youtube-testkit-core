<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Tests\TestCase;

class AnalyticsQueryResponseTest extends TestCase
{
    public function test_make_generic_response(): void
    {
        $columnHeaders = [
            ['name' => 'day', 'columnType' => 'DIMENSION', 'dataType' => 'STRING'],
            ['name' => 'views', 'columnType' => 'METRIC', 'dataType' => 'INTEGER'],
        ];

        $rows = [
            ['2024-01-01', 1000],
            ['2024-01-02', 1200],
        ];

        $response = AnalyticsQueryResponse::make($columnHeaders, $rows);
        $body = json_decode($response->body, true);

        $this->assertSame('youtubeAnalytics#resultTable', $body['kind']);
        $this->assertCount(2, $body['columnHeaders']);
        $this->assertCount(2, $body['rows']);

        $this->assertSame('day', $body['columnHeaders'][0]['name']);
        $this->assertSame('DIMENSION', $body['columnHeaders'][0]['columnType']);
        $this->assertSame('STRING', $body['columnHeaders'][0]['dataType']);

        $this->assertSame(['2024-01-01', 1000], $body['rows'][0]);
    }

    public function test_channel_overview(): void
    {
        $response = AnalyticsQueryResponse::channelOverview();
        $body = json_decode($response->body, true);

        $this->assertSame('youtubeAnalytics#resultTable', $body['kind']);
        $this->assertIsArray($body['columnHeaders']);
        $this->assertIsArray($body['rows']);

        // Check it has overview metrics
        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('views', $metricNames);
        $this->assertContains('estimatedMinutesWatched', $metricNames);
        $this->assertContains('subscribersGained', $metricNames);
    }

    public function test_daily_metrics(): void
    {
        $response = AnalyticsQueryResponse::dailyMetrics();
        $body = json_decode($response->body, true);

        $this->assertSame('youtubeAnalytics#resultTable', $body['kind']);
        
        // Check it has day dimension
        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('day', $metricNames);
        $this->assertContains('views', $metricNames);

        // Check rows have daily data
        $this->assertGreaterThan(0, count($body['rows']));
        $this->assertIsString($body['rows'][0][0]); // First column should be date string
    }

    public function test_top_videos(): void
    {
        $response = AnalyticsQueryResponse::topVideos();
        $body = json_decode($response->body, true);

        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('video', $metricNames);
        $this->assertContains('views', $metricNames);

        // Check rows have video data
        $this->assertGreaterThan(0, count($body['rows']));
        $this->assertIsString($body['rows'][0][0]); // First column should be video ID
    }

    public function test_traffic_sources(): void
    {
        $response = AnalyticsQueryResponse::trafficSources();
        $body = json_decode($response->body, true);

        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('insightTrafficSourceType', $metricNames);
        $this->assertContains('views', $metricNames);
    }

    public function test_traffic_sources_no_args_returns_all_columns_and_rows(): void
    {
        $response = AnalyticsQueryResponse::trafficSources();
        $body = json_decode($response->body, true);

        $this->assertCount(10, $body['columnHeaders']);
        $this->assertCount(10, $body['rows']);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame([
            'insightTrafficSourceType',
            'creatorContentType',
            'day',
            'liveOrOnDemand',
            'subscribedStatus',
            'engagedViews',
            'views',
            'estimatedMinutesWatched',
            'videoThumbnailImpressions',
            'videoThumbnailImpressionsClickRate',
        ], $names);
    }

    public function test_traffic_sources_filter_dimensions_only(): void
    {
        $response = AnalyticsQueryResponse::trafficSources(
            dimensions: ['day'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');

        // insightTrafficSourceType is always included + requested day + all 5 metrics
        $this->assertSame([
            'insightTrafficSourceType',
            'day',
            'engagedViews',
            'views',
            'estimatedMinutesWatched',
            'videoThumbnailImpressions',
            'videoThumbnailImpressionsClickRate',
        ], $names);

        $this->assertCount(10, $body['rows']);
    }

    public function test_traffic_sources_filter_metrics_only(): void
    {
        $response = AnalyticsQueryResponse::trafficSources(
            metrics: ['views', 'estimatedMinutesWatched'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');

        // All 5 dimensions + 2 requested metrics
        $this->assertSame([
            'insightTrafficSourceType',
            'creatorContentType',
            'day',
            'liveOrOnDemand',
            'subscribedStatus',
            'views',
            'estimatedMinutesWatched',
        ], $names);

        $this->assertCount(10, $body['rows']);
    }

    public function test_traffic_sources_filter_both_dimensions_and_metrics(): void
    {
        $response = AnalyticsQueryResponse::trafficSources(
            dimensions: ['creatorContentType'],
            metrics: ['views'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');

        // insightTrafficSourceType (always) + creatorContentType + views
        $this->assertSame([
            'insightTrafficSourceType',
            'creatorContentType',
            'views',
        ], $names);

        $this->assertCount(10, $body['rows']);
    }

    public function test_traffic_sources_row_data_aligns_with_filtered_columns(): void
    {
        $response = AnalyticsQueryResponse::trafficSources(
            dimensions: ['subscribedStatus'],
            metrics: ['engagedViews'],
        );
        $body = json_decode($response->body, true);

        // Columns: insightTrafficSourceType, subscribedStatus, engagedViews
        $this->assertCount(3, $body['columnHeaders']);

        // First row from fixture: RELATED_VIDEO, SUBSCRIBED, 450000
        $firstRow = $body['rows'][0];
        $this->assertCount(3, $firstRow);
        $this->assertSame('RELATED_VIDEO', $firstRow[0]);
        $this->assertSame('SUBSCRIBED', $firstRow[1]);
        $this->assertSame(450000, $firstRow[2]);

        // Last row from fixture: RELATED_VIDEO, SUBSCRIBED, 36000
        $lastRow = $body['rows'][9];
        $this->assertCount(3, $lastRow);
        $this->assertSame('RELATED_VIDEO', $lastRow[0]);
        $this->assertSame('SUBSCRIBED', $lastRow[1]);
        $this->assertSame(36000, $lastRow[2]);
    }

    public function test_traffic_sources_overrides_applied_after_filtering(): void
    {
        $response = AnalyticsQueryResponse::trafficSources(
            dimensions: [],
            metrics: ['views'],
            overrides: ['rows' => [['DIRECT', 99999]]],
        );
        $body = json_decode($response->body, true);

        // Overrides replace rows entirely
        $this->assertCount(1, $body['rows']);
        $this->assertSame(['DIRECT', 99999], $body['rows'][0]);
    }

    public function test_demographics(): void
    {
        $response = AnalyticsQueryResponse::demographics();
        $body = json_decode($response->body, true);

        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('ageGroup', $metricNames);
        $this->assertContains('gender', $metricNames);
        $this->assertContains('viewerPercentage', $metricNames);
    }

    public function test_geography(): void
    {
        $response = AnalyticsQueryResponse::geography();
        $body = json_decode($response->body, true);

        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('country', $metricNames);
        $this->assertContains('views', $metricNames);
    }

    public function test_device_types(): void
    {
        $response = AnalyticsQueryResponse::deviceTypes();
        $body = json_decode($response->body, true);

        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('deviceType', $metricNames);
        $this->assertContains('views', $metricNames);
    }

    public function test_video_analytics(): void
    {
        $response = AnalyticsQueryResponse::videoAnalytics();
        $body = json_decode($response->body, true);

        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('views', $metricNames);
        $this->assertContains('likes', $metricNames);
        $this->assertContains('comments', $metricNames);
    }

    public function test_video_types(): void
    {
        $response = AnalyticsQueryResponse::videoTypes();
        $body = json_decode($response->body, true);

        $metricNames = array_column($body['columnHeaders'], 'name');
        $this->assertContains('video', $metricNames);
        $this->assertContains('creatorContentType', $metricNames);
        $this->assertContains('views', $metricNames);
    }

    public function test_custom_overrides(): void
    {
        $response = AnalyticsQueryResponse::channelOverview([
            'rows' => [
                [999999, 500000, 250, 100, 5, 15000, 200, 800, 2500]
            ]
        ]);

        $body = json_decode($response->body, true);
        
        // Check the override values are present
        $this->assertSame(999999, $body['rows'][0][0]); // Custom views
    }
}