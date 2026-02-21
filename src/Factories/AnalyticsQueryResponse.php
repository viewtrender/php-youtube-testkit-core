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
     * @throws JsonException
     */
    public static function trafficSources(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('traffic-sources');

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