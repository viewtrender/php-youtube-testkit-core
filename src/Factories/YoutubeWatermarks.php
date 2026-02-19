<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeWatermarks
{
    /**
     * @throws JsonException
     */
    public static function set(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture();

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * @param array<string, mixed> $watermark
     * @throws JsonException
     */
    public static function setWithWatermark(array $watermark): FakeResponse
    {
        $base = self::loadFixture();

        return FakeResponse::make(self::mergeRecursive($base, $watermark));
    }

    /**
     * @throws JsonException
     */
    public static function watermark(array $overrides = []): array
    {
        return self::buildWatermark($overrides);
    }

    /**
     * Returns an empty response for watermarks.unset operations.
     */
    public static function empty(): FakeResponse
    {
        return FakeResponse::make([]);
    }

    /**
     * @throws JsonException
     */
    private static function buildWatermark(array $overrides = []): array
    {
        $fixture = self::loadFixture();

        return self::mergeRecursive($fixture, $overrides);
    }

    /**
     * @throws JsonException
     */
    private static function loadFixture(): array
    {
        static $fixture = null;

        if ($fixture === null) {
            $path = dirname(__DIR__) . '/Fixtures/youtube/watermarks-set.json';
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
