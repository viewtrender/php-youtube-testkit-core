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
        $this->assertNotEmpty($body['items']);
    }

    public function test_playlist_has_snippet_and_content_details(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $playlist = $body['items'][0];

        $this->assertSame('youtube#playlist', $playlist['kind']);
        $this->assertArrayHasKey('snippet', $playlist);
        $this->assertArrayHasKey('contentDetails', $playlist);
        $this->assertArrayHasKey('status', $playlist);
        $this->assertSame('Fake Playlist', $playlist['snippet']['title']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubePlaylist::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_playlist_with_overrides(): void
    {
        $playlist = YoutubePlaylist::playlist([
            'id' => 'custom-playlist-id',
            'snippet' => ['title' => 'My Playlist'],
        ]);

        $this->assertSame('custom-playlist-id', $playlist['id']);
        $this->assertSame('My Playlist', $playlist['snippet']['title']);
        $this->assertArrayHasKey('channelId', $playlist['snippet']);
    }

    public function test_list_with_playlists(): void
    {
        $response = YoutubePlaylist::listWithPlaylists([
            ['id' => 'pl-1', 'snippet' => ['title' => 'Playlist One']],
            ['id' => 'pl-2', 'snippet' => ['title' => 'Playlist Two']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('pl-1', $body['items'][0]['id']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_list_with_overrides(): void
    {
        $response = YoutubePlaylist::list(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }

    public function test_playlist_has_player_and_localizations(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $playlist = $body['items'][0];

        $this->assertArrayHasKey('player', $playlist);
        $this->assertArrayHasKey('embedHtml', $playlist['player']);
        $this->assertArrayHasKey('localizations', $playlist);
    }

    public function test_playlist_status_includes_privacy_and_podcast_status(): void
    {
        $body = json_decode(YoutubePlaylist::list()->body, true);
        $playlist = $body['items'][0];

        $this->assertArrayHasKey('privacyStatus', $playlist['status']);
        $this->assertArrayHasKey('podcastStatus', $playlist['status']);
        $this->assertSame('public', $playlist['status']['privacyStatus']);
    }
}
