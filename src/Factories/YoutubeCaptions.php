<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeCaptions
{
    /**
     * Generate a default captions list response
     *
     * @param array $overrides Optional overrides for the default fixture
     * @throws JsonException
     */
    public static function list(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture();

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * Generate a captions list response with custom items
     *
     * @param array<int, array<string, mixed>> $captions Custom caption items
     * @throws JsonException
     */
    public static function listWithCaptions(array $captions): FakeResponse
    {
        $items = array_map(fn (array $caption) => self::buildCaption($caption), $captions);

        $fixture = self::loadFixture();
        $fixture['items'] = $items;

        return FakeResponse::make($fixture);
    }

    /**
     * Generate an empty captions list response
     *
     * @throws JsonException
     */
    public static function empty(): FakeResponse
    {
        $fixture = self::loadFixture();
        $fixture['items'] = [];

        return FakeResponse::make($fixture);
    }

    /**
     * Generate a single caption item
     *
     * @param array $overrides Optional overrides for the default caption
     */
    public static function caption(array $overrides = []): array
    {
        return self::buildCaption($overrides);
    }

    /**
     * Build a caption array from base fixture with overrides
     *
     * @throws JsonException
     */
    private static function buildCaption(array $overrides = []): array
    {
        $fixture = self::loadFixture();
        $base = $fixture['items'][0];

        return self::mergeRecursive($base, $overrides);
    }

    /**
     * Load a fixture from the Fixtures directory
     *
     * @throws JsonException
     */
    private static function loadFixture(): array
    {
        static $fixture = null;

        if ($fixture === null) {
            $path = dirname(__DIR__) . '/Fixtures/youtube/captions-list.json';
            $fixture = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return $fixture;
    }

    /**
     * Recursively merge two arrays, replacing lists entirely
     */
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
