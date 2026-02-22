<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Concerns\HasPagination;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeVideo
{
    use HasPagination;
    /**
     * @throws JsonException
     */
    public static function list(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture();

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * @param array<int, array<string, mixed>> $videos
     * @throws JsonException
     */
    public static function listWithVideos(array $videos): FakeResponse
    {
        $items = array_map(fn (array $video) => self::buildVideo($video), $videos);

        $fixture = self::loadFixture();
        $fixture['items'] = $items;
        $fixture['pageInfo']['totalResults'] = count($items);
        unset($fixture['nextPageToken']);

        return FakeResponse::make($fixture);
    }

    public static function video(array $overrides = []): array
    {
        return self::buildVideo($overrides);
    }

    /**
     * @throws JsonException
     */
    public static function empty(): FakeResponse
    {
        $fixture = self::loadFixture();
        $fixture['items'] = [];
        $fixture['pageInfo']['totalResults'] = 0;
        unset($fixture['nextPageToken']);

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    private static function buildVideo(array $overrides = []): array
    {
        $fixture = self::loadFixture();
        $base = $fixture['items'][0];

        return self::mergeRecursive($base, $overrides);
    }

    /**
     * @throws JsonException
     */
    protected static function buildSingleItem(array $overrides = []): array
    {
        return self::buildVideo($overrides);
    }

    /**
     * @throws JsonException
     */
    private static function loadFixture(): array
    {
        static $fixture = null;

        if ($fixture === null) {
            $path = dirname(__DIR__) . '/Fixtures/youtube/videos-list.json';
            $fixture = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return $fixture;
    }

    private static function mergeRecursive(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && ! array_is_list($value)) {
                $base[$key] = self::mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
