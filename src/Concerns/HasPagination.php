<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Concerns;

use JsonException;
use Viewtrender\Youtube\Responses\FakeResponse;

/**
 * Provides pagination support for YouTube Data API list factories.
 *
 * Host classes must implement:
 * - protected static function loadFixture(): array
 * - protected static function buildSingleItem(array $overrides = []): array
 */
trait HasPagination
{
    /**
     * Generate multiple pages of fake items.
     *
     * @param int $pages Number of pages to generate
     * @param int $perPage Number of items per page
     * @return array<FakeResponse>
     * @throws JsonException
     */
    public static function paginated(int $pages = 2, int $perPage = 5): array
    {
        $responses = [];

        for ($page = 1; $page <= $pages; $page++) {
            $items = [];
            for ($i = 0; $i < $perPage; $i++) {
                $items[] = static::buildSingleItem();
            }

            $fixture = static::loadFixture();
            $fixture['items'] = $items;
            $fixture['pageInfo']['totalResults'] = $pages * $perPage;
            $fixture['pageInfo']['resultsPerPage'] = $perPage;

            if ($page < $pages) {
                $fixture['nextPageToken'] = 'page_token_' . ($page + 1);
            } else {
                unset($fixture['nextPageToken']);
            }

            $responses[] = FakeResponse::make($fixture);
        }

        return $responses;
    }

    /**
     * Generate pages from explicit item arrays.
     *
     * @param array<int, array<int, array<string, mixed>>> $pages Array of pages, each containing item arrays
     * @return array<FakeResponse>
     * @throws JsonException
     */
    public static function pages(array $pages): array
    {
        $responses = [];
        $totalItems = array_sum(array_map('count', $pages));
        $pageCount = count($pages);
        $pageNumber = 0;

        foreach ($pages as $pageItems) {
            $pageNumber++;
            $items = array_map(fn(array $item) => static::buildSingleItem($item), $pageItems);

            $fixture = static::loadFixture();
            $fixture['items'] = $items;
            $fixture['pageInfo']['totalResults'] = $totalItems;
            $fixture['pageInfo']['resultsPerPage'] = count($items);

            if ($pageNumber < $pageCount) {
                $fixture['nextPageToken'] = 'page_token_' . ($pageNumber + 1);
            } else {
                unset($fixture['nextPageToken']);
            }

            $responses[] = FakeResponse::make($fixture);
        }

        return $responses;
    }
}
