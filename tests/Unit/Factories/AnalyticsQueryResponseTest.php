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

    public function test_traffic_source_detail_no_args_returns_all_columns_and_rows(): void
    {
        $response = AnalyticsQueryResponse::trafficSourceDetail();
        $body = json_decode($response->body, true);

        $this->assertCount(7, $body['columnHeaders']);
        $this->assertCount(10, $body['rows']);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame([
            'insightTrafficSourceDetail',
            'creatorContentType',
            'engagedViews',
            'views',
            'estimatedMinutesWatched',
            'videoThumbnailImpressions',
            'videoThumbnailImpressionsClickRate',
        ], $names);
    }

    public function test_traffic_source_detail_filter_dimensions_only(): void
    {
        $response = AnalyticsQueryResponse::trafficSourceDetail(
            dimensions: ['creatorContentType'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');

        // insightTrafficSourceDetail always included + requested creatorContentType + all 5 metrics
        $this->assertSame([
            'insightTrafficSourceDetail',
            'creatorContentType',
            'engagedViews',
            'views',
            'estimatedMinutesWatched',
            'videoThumbnailImpressions',
            'videoThumbnailImpressionsClickRate',
        ], $names);

        $this->assertCount(10, $body['rows']);
    }

    public function test_traffic_source_detail_filter_metrics_only(): void
    {
        $response = AnalyticsQueryResponse::trafficSourceDetail(
            metrics: ['views', 'estimatedMinutesWatched'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');

        // All 2 dimensions + 2 requested metrics
        $this->assertSame([
            'insightTrafficSourceDetail',
            'creatorContentType',
            'views',
            'estimatedMinutesWatched',
        ], $names);

        $this->assertCount(10, $body['rows']);
    }

    public function test_traffic_source_detail_filter_both_dimensions_and_metrics(): void
    {
        $response = AnalyticsQueryResponse::trafficSourceDetail(
            dimensions: ['creatorContentType'],
            metrics: ['views'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');

        // insightTrafficSourceDetail (always) + creatorContentType + views
        $this->assertSame([
            'insightTrafficSourceDetail',
            'creatorContentType',
            'views',
        ], $names);

        $this->assertCount(10, $body['rows']);
    }

    public function test_traffic_source_detail_row_data_aligns_with_filtered_columns(): void
    {
        $response = AnalyticsQueryResponse::trafficSourceDetail(
            metrics: ['engagedViews'],
        );
        $body = json_decode($response->body, true);

        // Columns: insightTrafficSourceDetail, creatorContentType, engagedViews
        $this->assertCount(3, $body['columnHeaders']);

        // First row: "how to edit videos", VIDEO_ON_DEMAND, 45000
        $firstRow = $body['rows'][0];
        $this->assertCount(3, $firstRow);
        $this->assertSame('how to edit videos', $firstRow[0]);
        $this->assertSame('VIDEO_ON_DEMAND', $firstRow[1]);
        $this->assertSame(45000, $firstRow[2]);

        // Last row: "TrueView in-stream", VIDEO_ON_DEMAND, 3000
        $lastRow = $body['rows'][9];
        $this->assertCount(3, $lastRow);
        $this->assertSame('TrueView in-stream', $lastRow[0]);
        $this->assertSame('VIDEO_ON_DEMAND', $lastRow[1]);
        $this->assertSame(3000, $lastRow[2]);
    }

    public function test_traffic_source_detail_overrides_applied_after_filtering(): void
    {
        $response = AnalyticsQueryResponse::trafficSourceDetail(
            dimensions: [],
            metrics: ['views'],
            overrides: ['rows' => [['my search term', 'VIDEO_ON_DEMAND', 99999]]],
        );
        $body = json_decode($response->body, true);

        // Overrides replace rows entirely
        $this->assertCount(1, $body['rows']);
        $this->assertSame(['my search term', 'VIDEO_ON_DEMAND', 99999], $body['rows'][0]);
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

    // --- Playback Locations ---

    public function test_playback_locations(): void
    {
        $response = AnalyticsQueryResponse::playbackLocations();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertContains('insightPlaybackLocationType', $names);
        $this->assertContains('views', $names);
        $this->assertContains('estimatedMinutesWatched', $names);
        $this->assertGreaterThan(0, count($body['rows']));
    }

    public function test_playback_locations_no_args_returns_all_columns(): void
    {
        $response = AnalyticsQueryResponse::playbackLocations();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame([
            'insightPlaybackLocationType',
            'engagedViews',
            'views',
            'estimatedMinutesWatched',
        ], $names);

        $this->assertCount(5, $body['rows']);
    }

    public function test_playback_locations_filter_metrics(): void
    {
        $response = AnalyticsQueryResponse::playbackLocations(
            metrics: ['views'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame(['insightPlaybackLocationType', 'views'], $names);
        $this->assertCount(5, $body['rows']);

        // First row should be WATCH with the most views
        $this->assertSame('WATCH', $body['rows'][0][0]);
    }

    public function test_playback_locations_overrides(): void
    {
        $response = AnalyticsQueryResponse::playbackLocations(
            overrides: ['rows' => [['EMBEDDED', 500, 500, 100]]],
        );
        $body = json_decode($response->body, true);

        $this->assertCount(1, $body['rows']);
        $this->assertSame('EMBEDDED', $body['rows'][0][0]);
    }

    // --- Operating Systems ---

    public function test_operating_systems(): void
    {
        $response = AnalyticsQueryResponse::operatingSystems();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertContains('operatingSystem', $names);
        $this->assertContains('views', $names);
        $this->assertGreaterThan(0, count($body['rows']));
    }

    public function test_operating_systems_no_args_returns_all_columns(): void
    {
        $response = AnalyticsQueryResponse::operatingSystems();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame([
            'operatingSystem',
            'views',
            'estimatedMinutesWatched',
            'engagedViews',
        ], $names);

        $this->assertCount(17, $body['rows']);

        // First row should be WINDOWS (most views)
        $this->assertSame('WINDOWS', $body['rows'][0][0]);
    }

    public function test_operating_systems_filter_metrics(): void
    {
        $response = AnalyticsQueryResponse::operatingSystems(
            metrics: ['views'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame(['operatingSystem', 'views'], $names);
        $this->assertCount(17, $body['rows']);
    }

    // --- Sharing Service ---

    public function test_sharing_service(): void
    {
        $response = AnalyticsQueryResponse::sharingService();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertContains('sharingService', $names);
        $this->assertContains('shares', $names);
        $this->assertGreaterThan(0, count($body['rows']));
    }

    public function test_sharing_service_no_args_returns_all_columns(): void
    {
        $response = AnalyticsQueryResponse::sharingService();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame(['sharingService', 'shares'], $names);

        $this->assertCount(7, $body['rows']);

        // First row should be COPY_PASTE (most shares)
        $this->assertSame('COPY_PASTE', $body['rows'][0][0]);
    }

    public function test_sharing_service_overrides(): void
    {
        $response = AnalyticsQueryResponse::sharingService(
            overrides: ['rows' => [['TWITTER', 42]]],
        );
        $body = json_decode($response->body, true);

        $this->assertCount(1, $body['rows']);
        $this->assertSame(['TWITTER', 42], $body['rows'][0]);
    }

    // --- Device + Operating System Combo ---

    public function test_device_operating_system(): void
    {
        $response = AnalyticsQueryResponse::deviceOperatingSystem();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertContains('deviceType', $names);
        $this->assertContains('operatingSystem', $names);
        $this->assertContains('views', $names);
        $this->assertGreaterThan(0, count($body['rows']));
    }

    public function test_device_operating_system_no_args_returns_all_columns(): void
    {
        $response = AnalyticsQueryResponse::deviceOperatingSystem();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame([
            'deviceType',
            'operatingSystem',
            'engagedViews',
            'views',
            'estimatedMinutesWatched',
        ], $names);

        $this->assertCount(21, $body['rows']);

        // First row should be DESKTOP + WINDOWS (most views)
        $this->assertSame('DESKTOP', $body['rows'][0][0]);
        $this->assertSame('WINDOWS', $body['rows'][0][1]);
    }

    public function test_device_operating_system_filter_metrics(): void
    {
        $response = AnalyticsQueryResponse::deviceOperatingSystem(
            metrics: ['views'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertSame(['deviceType', 'operatingSystem', 'views'], $names);
        $this->assertCount(21, $body['rows']);
    }

    public function test_device_operating_system_filter_dimensions(): void
    {
        $response = AnalyticsQueryResponse::deviceOperatingSystem(
            dimensions: ['operatingSystem'],
        );
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        // deviceType always included + operatingSystem + all metrics
        $this->assertSame([
            'deviceType',
            'operatingSystem',
            'engagedViews',
            'views',
            'estimatedMinutesWatched',
        ], $names);
    }

    // --- Audience Retention ---

    public function test_audience_retention(): void
    {
        $response = AnalyticsQueryResponse::audienceRetention();
        $body = json_decode($response->body, true);

        $names = array_column($body['columnHeaders'], 'name');
        $this->assertContains('elapsedVideoTimeRatio', $names);
        $this->assertContains('audienceWatchRatio', $names);
        $this->assertContains('relativeRetentionPerformance', $names);

        // 100 data points
        $this->assertCount(100, $body['rows']);
    }

    public function test_audience_retention_data_types(): void
    {
        $response = AnalyticsQueryResponse::audienceRetention();
        $body = json_decode($response->body, true);

        // All columns should be FLOAT
        foreach ($body['columnHeaders'] as $header) {
            $this->assertSame('FLOAT', $header['dataType'], "Column {$header['name']} should be FLOAT");
        }

        // First data point should be at 0.01
        $this->assertSame(0.01, $body['rows'][0][0]);

        // Values should be floats
        $this->assertIsFloat($body['rows'][0][1]); // audienceWatchRatio
        $this->assertIsFloat($body['rows'][0][2]); // relativeRetentionPerformance
    }

    public function test_audience_retention_overrides(): void
    {
        $response = AnalyticsQueryResponse::audienceRetention([
            'rows' => [[0.5, 0.45, 1.0]],
        ]);
        $body = json_decode($response->body, true);

        $this->assertCount(1, $body['rows']);
        $this->assertSame(0.5, $body['rows'][0][0]);
    }

    // --- Overrides ---

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