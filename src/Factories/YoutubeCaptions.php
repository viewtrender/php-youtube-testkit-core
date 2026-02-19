<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use Google\Service\YouTube\Caption;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeCaptions
{
    /**
     * Generate a default captions list response
     *
     * @param array $overrides Optional overrides for the default fixture
     * @return array
     */
    public static function list(array $overrides = []): array
    {
        $defaultFixture = self::loadFixture('captions-list.json');
        return self::mergeRecursive($defaultFixture, $overrides);
    }

    /**
     * Generate a captions list response with custom items
     *
     * @param array $items Custom caption items
     * @return array
     */
    public static function listWithCaptions(array $items): array
    {
        $baseResponse = self::list();
        $baseResponse['items'] = $items;
        return $baseResponse;
    }

    /**
     * Generate an empty captions list response
     *
     * @return array
     */
    public static function empty(): array
    {
        return [
            'kind' => 'youtube#captionListResponse',
            'etag' => '',
            'items' => []
        ];
    }

    /**
     * Generate a single caption item
     *
     * @param array $overrides Optional overrides for the default caption
     * @return array
     */
    public static function caption(array $overrides = []): array
    {
        $defaultCaption = [
            'kind' => 'youtube#caption',
            'etag' => '',
            'id' => 'caption-id',
            'snippet' => [
                'videoId' => 'video-id',
                'language' => 'en',
                'name' => 'English',
                'isDraft' => false,
                'isAutoSynced' => false,
                'status' => 'serving'
            ]
        ];

        return self::mergeRecursive($defaultCaption, $overrides);
    }

    /**
     * Load a fixture from the Fixtures directory
     *
     * @param string $filename Fixture filename
     * @return array
     */
    private static function loadFixture(string $filename): array
    {
        $path = __DIR__ . '/../Fixtures/youtube/' . $filename;
        if (!file_exists($path)) {
            return self::empty();
        }

        $json = file_get_contents($path);
        return json_decode($json, true);
    }

    /**
     * Recursively merge two arrays, replacing lists entirely
     *
     * @param array $base Base array
     * @param array $overrides Overrides array
     * @return array
     */
    private static function mergeRecursive(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value)) {
                $base[$key] = isset($base[$key]) && is_array($base[$key])
                    ? self::mergeRecursive($base[$key], $value)
                    : $value;
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}