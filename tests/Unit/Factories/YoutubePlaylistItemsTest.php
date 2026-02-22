<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubePlaylistItems;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubePlaylistItemsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubePlaylistItems::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubePlaylistItems::list()->body, true);

        $this->assertSame('youtube#playlistItemListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('pageInfo', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_list_playlist_item_has_snippet_and_content_details(): void
    {
        $body = json_decode(YoutubePlaylistItems::list()->body, true);
        $item = $body['items'][0];

        $this->assertSame('youtube#playlistItem', $item['kind']);
        $this->assertArrayHasKey('snippet', $item);
        $this->assertArrayHasKey('contentDetails', $item);
        $this->assertArrayHasKey('status', $item);
        $this->assertSame('Fake Playlist Item Title', $item['snippet']['title']);
        $this->assertSame('dQw4w9WgXcQ', $item['snippet']['resourceId']['videoId']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubePlaylistItems::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_playlist_item_returns_single_item_array(): void
    {
        $item = YoutubePlaylistItems::playlistItem();

        $this->assertSame('youtube#playlistItem', $item['kind']);
        $this->assertSame('UExfZmFrZV9wbGF5bGlzdF9pZC5kUXc0dzlXZ1hjUQ', $item['id']);
        $this->assertSame('dQw4w9WgXcQ', $item['snippet']['resourceId']['videoId']);
    }

    public function test_playlist_item_with_overrides(): void
    {
        $item = YoutubePlaylistItems::playlistItem([
            'id' => 'custom-item-id',
            'snippet' => [
                'title' => 'Custom Title',
                'position' => 5,
                'resourceId' => ['videoId' => 'custom-video-id'],
            ],
        ]);

        $this->assertSame('custom-item-id', $item['id']);
        $this->assertSame('Custom Title', $item['snippet']['title']);
        $this->assertSame(5, $item['snippet']['position']);
        $this->assertSame('custom-video-id', $item['snippet']['resourceId']['videoId']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('playlistId', $item['snippet']);
        $this->assertArrayHasKey('channelId', $item['snippet']);
    }

    public function test_list_with_playlist_items(): void
    {
        $response = YoutubePlaylistItems::listWithPlaylistItems([
            [
                'id' => 'item-1',
                'snippet' => [
                    'title' => 'First Video',
                    'position' => 0,
                    'resourceId' => ['videoId' => 'vid-1'],
                ],
            ],
            [
                'id' => 'item-2',
                'snippet' => [
                    'title' => 'Second Video',
                    'position' => 1,
                    'resourceId' => ['videoId' => 'vid-2'],
                ],
            ],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('item-1', $body['items'][0]['id']);
        $this->assertSame('item-2', $body['items'][1]['id']);
        $this->assertSame('vid-1', $body['items'][0]['snippet']['resourceId']['videoId']);
        $this->assertSame('vid-2', $body['items'][1]['snippet']['resourceId']['videoId']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_list_with_top_level_overrides(): void
    {
        $response = YoutubePlaylistItems::list([
            'nextPageToken' => 'CUSTOM_TOKEN',
        ]);

        $body = json_decode($response->body, true);

        $this->assertSame('CUSTOM_TOKEN', $body['nextPageToken']);
    }

    public function test_list_with_playlist_items_does_not_include_next_page_token(): void
    {
        $response = YoutubePlaylistItems::listWithPlaylistItems([
            ['id' => 'item-1'],
            ['id' => 'item-2'],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubePlaylistItems::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubePlaylistItems::paginated(pages: 3, perPage: 2);

        $this->assertCount(3, $responses);

        // First page has nextPageToken
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);

        // Second page has nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(2, $body2['items']);
        $this->assertSame('page_token_3', $body2['nextPageToken']);

        // Last page has no nextPageToken
        $body3 = json_decode($responses[2]->body, true);
        $this->assertCount(2, $body3['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body3);
    }

    public function test_paginated_sets_correct_total_results(): void
    {
        $responses = YoutubePlaylistItems::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubePlaylistItems::pages([
            [
                ['snippet' => ['title' => 'Page 1 Item 1']],
                ['snippet' => ['title' => 'Page 1 Item 2']],
            ],
            [
                ['snippet' => ['title' => 'Page 2 Item 1']],
            ],
        ]);

        $this->assertCount(2, $responses);

        // First page
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('Page 1 Item 1', $body1['items'][0]['snippet']['title']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);
        $this->assertSame(3, $body1['pageInfo']['totalResults']);

        // Second page (last) has no nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(1, $body2['items']);
        $this->assertSame('Page 2 Item 1', $body2['items'][0]['snippet']['title']);
        $this->assertArrayNotHasKey('nextPageToken', $body2);
    }

    public function test_pages_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubePlaylistItems::pages([
            [['snippet' => ['title' => 'Only Item']]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
