<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Responses\FakeResponse;

class ReportingReportType
{
    /**
     * @throws JsonException
     */
    public static function list(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture();

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * @param array<int, array<string, mixed>> $reportTypes
     * @throws JsonException
     */
    public static function listWithTypes(array $reportTypes): FakeResponse
    {
        $fixture = self::loadFixture();
        $fixture['reportTypes'] = $reportTypes;

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    public static function empty(): FakeResponse
    {
        $fixture = self::loadFixture();
        $fixture['reportTypes'] = [];

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    public static function reportType(array $overrides = []): array
    {
        $fixture = self::loadFixture();
        $base = $fixture['reportTypes'][0];

        return self::mergeRecursive($base, $overrides);
    }

    /**
     * @throws JsonException
     */
    private static function loadFixture(): array
    {
        static $fixture = null;

        if ($fixture === null) {
            $path = dirname(__DIR__) . '/Fixtures/reporting/report-types-list.json';
            $fixture = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return $fixture;
    }

    private static function mergeRecursive(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
                $base[$key] = self::mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}