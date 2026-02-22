<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubePlaylistTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubePlaylist::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);

        $this->assertSame('youtube#playlistListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('pageInfo', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_playlist_has_snippet_and_status(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $playlist = $body['items'][0];

        $this->assertSame('youtube#playlist', $playlist['kind']);
        $this->assertArrayHasKey('snippet', $playlist);
        $this->assertArrayHasKey('status', $playlist);
        $this->assertArrayHasKey('contentDetails', $playlist);
        $this->assertArrayHasKey('player', $playlist);
        $this->assertSame('Fake Playlist', $playlist['snippet']['title']);
    }

    public function test_playlist_snippet_has_required_fields(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $snippet = $body['items'][0]['snippet'];

        $this->assertArrayHasKey('publishedAt', $snippet);
        $this->assertArrayHasKey('channelId', $snippet);
        $this->assertArrayHasKey('title', $snippet);
        $this->assertArrayHasKey('description', $snippet);
        $this->assertArrayHasKey('thumbnails', $snippet);
        $this->assertArrayHasKey('channelTitle', $snippet);
        $this->assertArrayHasKey('localized', $snippet);
    }

    public function test_playlist_thumbnails_has_all_sizes(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $thumbnails = $body['items'][0]['snippet']['thumbnails'];

        $this->assertArrayHasKey('default', $thumbnails);
        $this->assertArrayHasKey('medium', $thumbnails);
        $this->assertArrayHasKey('high', $thumbnails);
        $this->assertArrayHasKey('standard', $thumbnails);
        $this->assertArrayHasKey('maxres', $thumbnails);

        // Verify thumbnail structure
        $this->assertArrayHasKey('url', $thumbnails['default']);
        $this->assertArrayHasKey('width', $thumbnails['default']);
        $this->assertArrayHasKey('height', $thumbnails['default']);
    }

    public function test_playlist_status_has_privacy_status(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $status = $body['items'][0]['status'];

        $this->assertArrayHasKey('privacyStatus', $status);
        $this->assertSame('public', $status['privacyStatus']);
    }

    public function test_playlist_content_details_has_item_count(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $contentDetails = $body['items'][0]['contentDetails'];

        $this->assertArrayHasKey('itemCount', $contentDetails);
        $this->assertIsInt($contentDetails['itemCount']);
    }

    public function test_playlist_player_has_embed_html(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $player = $body['items'][0]['player'];

        $this->assertArrayHasKey('embedHtml', $player);
        $this->assertStringContainsString('<iframe', $player['embedHtml']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubePlaylist::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_playlist_returns_single_item_array(): void
    {
        $playlist = YoutubePlaylist::playlist();

        $this->assertSame('youtube#playlist', $playlist['kind']);
        $this->assertSame('PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf', $playlist['id']);
        $this->assertSame('Fake Playlist', $playlist['snippet']['title']);
    }

    public function test_playlist_with_overrides(): void
    {
        $playlist = YoutubePlaylist::playlist([
            'id' => 'custom-playlist-id',
            'snippet' => [
                'title' => 'Custom Playlist',
                'description' => 'Custom description',
            ],
        ]);

        $this->assertSame('custom-playlist-id', $playlist['id']);
        $this->assertSame('Custom Playlist', $playlist['snippet']['title']);
        $this->assertSame('Custom description', $playlist['snippet']['description']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('channelId', $playlist['snippet']);
        $this->assertArrayHasKey('thumbnails', $playlist['snippet']);
    }

    public function test_list_with_playlists(): void
    {
        $response = YoutubePlaylist::listWithPlaylists([
            [
                'id' => 'playlist-1',
                'snippet' => ['title' => 'First Playlist'],
            ],
            [
                'id' => 'playlist-2',
                'snippet' => ['title' => 'Second Playlist'],
            ],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('playlist-1', $body['items'][0]['id']);
        $this->assertSame('playlist-2', $body['items'][1]['id']);
        $this->assertSame('First Playlist', $body['items'][0]['snippet']['title']);
        $this->assertSame('Second Playlist', $body['items'][1]['snippet']['title']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_list_with_top_level_overrides(): void
    {
        $response = YoutubePlaylist::list([
            'nextPageToken' => 'CUSTOM_TOKEN',
        ]);

        $body = json_decode($response->body, true);

        $this->assertSame('CUSTOM_TOKEN', $body['nextPageToken']);
    }

    public function test_fixture_has_localizations(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $playlist = $body['items'][0];

        $this->assertArrayHasKey('localizations', $playlist);
        $this->assertArrayHasKey('es', $playlist['localizations']);
        $this->assertArrayHasKey('fr', $playlist['localizations']);
        $this->assertArrayHasKey('title', $playlist['localizations']['es']);
        $this->assertArrayHasKey('description', $playlist['localizations']['es']);
    }

    public function test_list_with_playlists_does_not_include_next_page_token(): void
    {
        $response = YoutubePlaylist::listWithPlaylists([
            ['id' => 'playlist-1'],
            ['id' => 'playlist-2'],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_empty_does_not_include_next_page_token(): void
    {
        $body = json_decode(YoutubePlaylist::empty()->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubePlaylist::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubePlaylist::paginated(pages: 3, perPage: 2);

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
        $responses = YoutubePlaylist::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubePlaylist::pages([
            [
                ['snippet' => ['title' => 'Page 1 Playlist 1']],
                ['snippet' => ['title' => 'Page 1 Playlist 2']],
            ],
            [
                ['snippet' => ['title' => 'Page 2 Playlist 1']],
            ],
        ]);

        $this->assertCount(2, $responses);

        // First page
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('Page 1 Playlist 1', $body1['items'][0]['snippet']['title']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);
        $this->assertSame(3, $body1['pageInfo']['totalResults']);

        // Second page (last) has no nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(1, $body2['items']);
        $this->assertSame('Page 2 Playlist 1', $body2['items'][0]['snippet']['title']);
        $this->assertArrayNotHasKey('nextPageToken', $body2);
    }

    public function test_pages_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubePlaylist::pages([
            [['snippet' => ['title' => 'Only Playlist']]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
