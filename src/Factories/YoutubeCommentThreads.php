<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Concerns\HasPagination;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeCommentThreads
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
     * @param array<int, array<string, mixed>> $commentThreads
     * @throws JsonException
     */
    public static function listWithCommentThreads(array $commentThreads): FakeResponse
    {
        $items = array_map(fn(array $commentThread) => self::buildCommentThread($commentThread), $commentThreads);

        $fixture = self::loadFixture();
        $fixture['items'] = $items;
        $fixture['pageInfo']['totalResults'] = count($items);
        unset($fixture['nextPageToken']);

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    public static function commentThread(array $overrides = []): array
    {
        return self::buildCommentThread($overrides);
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
    private static function buildCommentThread(array $overrides = []): array
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
        return self::buildCommentThread($overrides);
    }

    /**
     * @throws JsonException
     */
    private static function loadFixture(): array
    {
        static $fixture = null;

        if ($fixture === null) {
            $path = dirname(__DIR__) . '/Fixtures/youtube/comment-threads-list.json';
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
