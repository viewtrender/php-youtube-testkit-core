<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Responses\FakeResponse;

class AnalyticsQueryResponse
{
    /**
     * Build a generic Analytics query response
     *
     * @param array<array<string, mixed>> $columnHeaders
     * @param array<array<mixed>> $rows
     */
    public static function make(array $columnHeaders, array $rows): FakeResponse
    {
        return FakeResponse::make([
            'kind' => 'youtubeAnalytics#resultTable',
            'columnHeaders' => array_map(function ($header) {
                return [
                    'name' => $header['name'],
                    'columnType' => $header['columnType'] ?? 'METRIC',
                    'dataType' => $header['dataType'] ?? 'INTEGER',
                ];
            }, $columnHeaders),
            'rows' => $rows,
        ]);
    }

    /**
     * Channel overview metrics (no dimensions - aggregate totals)
     *
     * @throws JsonException
     */
    public static function channelOverview(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('channel-overview');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Daily metrics with day dimension
     *
     * @throws JsonException
     */
    public static function dailyMetrics(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('daily-metrics');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Top videos with video dimension
     *
     * @throws JsonException
     */
    public static function topVideos(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('top-videos');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Traffic sources breakdown
     *
     * The fixture contains the maximal column set (all 5 dimensions + all 5 metrics).
     * Pass $dimensions and/or $metrics to filter down to only the columns you need.
     * insightTrafficSourceType is always included as it's required by the API spec.
     *
     * @param  string[]  $dimensions  Dimension column names to include (insightTrafficSourceType always included)
     * @param  string[]  $metrics     Metric column names to include (all if empty)
     * @param  array     $overrides   Raw overrides applied after column filtering
     *
     * @throws JsonException
     */
    public static function trafficSources(array $dimensions = [], array $metrics = [], array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('traffic-sources');

        $fixture = self::filterColumns($fixture, $dimensions, $metrics, 'insightTrafficSourceType');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Traffic source detail breakdown
     *
     * The fixture contains the maximal column set (2 dimensions + 5 metrics).
     * Pass $dimensions and/or $metrics to filter down to only the columns you need.
     * insightTrafficSourceDetail is always included as it's required by the API spec.
     *
     * @param  string[]  $dimensions  Dimension column names to include (insightTrafficSourceDetail always included)
     * @param  string[]  $metrics     Metric column names to include (all if empty)
     * @param  array     $overrides   Raw overrides applied after column filtering
     *
     * @throws JsonException
     */
    public static function trafficSourceDetail(array $dimensions = [], array $metrics = [], array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('traffic-source-detail');

        $fixture = self::filterColumns($fixture, $dimensions, $metrics, 'insightTrafficSourceDetail');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Demographics breakdown by age group and gender
     *
     * @throws JsonException
     */
    public static function demographics(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('demographics');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Geography breakdown by country
     *
     * @throws JsonException
     */
    public static function geography(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('geography');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Device types breakdown
     *
     * @throws JsonException
     */
    public static function deviceTypes(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('device-types');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Playback locations breakdown
     *
     * @param  string[]  $dimensions  Dimension column names to include (insightPlaybackLocationType always included)
     * @param  string[]  $metrics     Metric column names to include (all if empty)
     * @param  array     $overrides   Raw overrides applied after column filtering
     *
     * @throws JsonException
     */
    public static function playbackLocations(array $dimensions = [], array $metrics = [], array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('playback-locations');

        $fixture = self::filterColumns($fixture, $dimensions, $metrics, 'insightPlaybackLocationType');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Operating system breakdown
     *
     * @param  string[]  $dimensions  Dimension column names to include (operatingSystem always included)
     * @param  string[]  $metrics     Metric column names to include (all if empty)
     * @param  array     $overrides   Raw overrides applied after column filtering
     *
     * @throws JsonException
     */
    public static function operatingSystems(array $dimensions = [], array $metrics = [], array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('operating-systems');

        $fixture = self::filterColumns($fixture, $dimensions, $metrics, 'operatingSystem');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Sharing service breakdown
     *
     * @param  string[]  $dimensions  Dimension column names to include (sharingService always included)
     * @param  string[]  $metrics     Metric column names to include (all if empty)
     * @param  array     $overrides   Raw overrides applied after column filtering
     *
     * @throws JsonException
     */
    public static function sharingService(array $dimensions = [], array $metrics = [], array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('sharing-service');

        $fixture = self::filterColumns($fixture, $dimensions, $metrics, 'sharingService');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Device type + operating system combo breakdown
     *
     * @param  string[]  $dimensions  Dimension column names to include (deviceType always included)
     * @param  string[]  $metrics     Metric column names to include (all if empty)
     * @param  array     $overrides   Raw overrides applied after column filtering
     *
     * @throws JsonException
     */
    public static function deviceOperatingSystem(array $dimensions = [], array $metrics = [], array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('device-os-combo');

        $fixture = self::filterColumns($fixture, $dimensions, $metrics, 'deviceType');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Audience retention curve (per-video)
     *
     * Returns 100 data points (elapsedVideoTimeRatio from 0.01 to 1.00)
     * with audienceWatchRatio and relativeRetentionPerformance.
     *
     * @throws JsonException
     */
    public static function audienceRetention(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('audience-retention');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Video analytics for specific video
     *
     * @throws JsonException
     */
    public static function videoAnalytics(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('video-analytics');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Video types breakdown
     *
     * @throws JsonException
     */
    public static function videoTypes(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('video-types');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Filter a fixture down to specific dimensions and metrics.
     *
     * @param  array    $fixture            The full fixture data
     * @param  string[] $dimensions         Dimension names to keep (empty = all)
     * @param  string[] $metrics            Metric names to keep (empty = all)
     * @param  string   $requiredDimension  Always included dimension
     */
    private static function filterColumns(array $fixture, array $dimensions, array $metrics, string $requiredDimension): array
    {
        if (empty($dimensions) && empty($metrics)) {
            return $fixture;
        }

        $headers = $fixture['columnHeaders'];

        // Determine which columns to keep by index
        $keepIndices = [];
        foreach ($headers as $i => $header) {
            $name = $header['name'];
            $type = $header['columnType'];

            if ($type === 'DIMENSION') {
                if (empty($dimensions) || $name === $requiredDimension || in_array($name, $dimensions, true)) {
                    $keepIndices[] = $i;
                }
            } else {
                // METRIC
                if (empty($metrics) || in_array($name, $metrics, true)) {
                    $keepIndices[] = $i;
                }
            }
        }

        $fixture['columnHeaders'] = array_values(array_map(
            fn ($i) => $headers[$i],
            $keepIndices
        ));

        $fixture['rows'] = array_values(array_map(
            fn ($row) => array_values(array_map(fn ($i) => $row[$i], $keepIndices)),
            $fixture['rows']
        ));

        return $fixture;
    }

    /**
     * Load fixture from analytics directory
     *
     * @throws JsonException
     */
    private static function loadFixture(string $name): array
    {
        static $fixtures = [];

        if (!isset($fixtures[$name])) {
            $path = dirname(__DIR__) . "/Fixtures/analytics/{$name}.json";
            $fixtures[$name] = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return $fixtures[$name];
    }
}