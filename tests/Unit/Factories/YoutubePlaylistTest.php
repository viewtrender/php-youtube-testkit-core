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
}
