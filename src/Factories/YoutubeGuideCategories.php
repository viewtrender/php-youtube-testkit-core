<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Responses\FakeResponse;

/**
 * Factory for generating fake YouTube guideCategories API responses.
 *
 * NOTE: The YouTube Data API guideCategories endpoint has been deprecated by Google
 * and now returns 404 Not Found. This factory remains available for testing legacy
 * code that may have integrated with this endpoint before its removal.
 *
 * @see https://developers.google.com/youtube/v3/docs/guideCategories
 * @deprecated The underlying YouTube API endpoint is no longer available
 */
class YoutubeGuideCategories
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
     * @param array<int, array<string, mixed>> $guideCategories
     * @throws JsonException
     */
    public static function listWithGuideCategories(array $guideCategories): FakeResponse
    {
        $items = array_map(fn(array $guideCategory) => self::buildGuideCategory($guideCategory), $guideCategories);

        $fixture = self::loadFixture();
        $fixture['items'] = $items;

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    public static function guideCategory(array $overrides = []): array
    {
        return self::buildGuideCategory($overrides);
    }

    /**
     * @throws JsonException
     */
    public static function empty(): FakeResponse
    {
        $fixture = self::loadFixture();
        $fixture['items'] = [];

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    private static function buildGuideCategory(array $overrides = []): array
    {
        $fixture = self::loadFixture();
        $base = $fixture['items'][0];

        return self::mergeRecursive($base, $overrides);
    }

    /**
     * @throws JsonException
     */
    private static function loadFixture(): array
    {
        static $fixture = null;

        if ($fixture === null) {
            $path = dirname(__DIR__) . '/Fixtures/youtube/guide-categories-list.json';
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
