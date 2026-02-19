<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Integration;

use Google\Service\Exception as GoogleServiceException;
use Google\Service\YouTube;
use Google\Service\YouTube\ChannelListResponse;
use Google\Service\YouTube\PlaylistListResponse;
use Google\Service\YouTube\SearchListResponse;
use Google\Service\YouTube\VideoListResponse;
use Psr\Http\Message\RequestInterface;
use Viewtrender\Youtube\Exceptions\StrayRequestException;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Factories\YoutubePlaylist;
use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Responses\ErrorResponse;
use Viewtrender\Youtube\Tests\TestCase;
use Viewtrender\Youtube\YoutubeDataApi;

class DataApiTest extends TestCase
{
    public function test_list_videos_returns_video_list_response(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => 'dQw4w9WgXcQ']);

        $this->assertInstanceOf(VideoListResponse::class, $response);
        $this->assertCount(1, $response->getItems());
        $this->assertSame('Fake Video Title', $response->getItems()[0]->getSnippet()->getTitle());
        $this->assertSame('1500000000', $response->getItems()[0]->getStatistics()->getViewCount());
    }

    public function test_list_videos_with_custom_data(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::listWithVideos([
                ['id' => 'abc123', 'snippet' => ['title' => 'My Custom Video']],
            ]),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $response = $youtube->videos->listVideos('snippet', ['id' => 'abc123']);

        $this->assertSame('My Custom Video', $response->getItems()[0]->getSnippet()->getTitle());
    }

    public function test_list_channels_returns_channel_list_response(): void
    {
        YoutubeDataApi::fake([
            YoutubeChannel::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $response = $youtube->channels->listChannels('snippet,statistics', ['id' => 'UCuAXFkgsw1L7xaCfnd5JJOw']);

        $this->assertInstanceOf(ChannelListResponse::class, $response);
        $this->assertCount(1, $response->getItems());
        $this->assertSame('Fake Channel', $response->getItems()[0]->getSnippet()->getTitle());
    }

    public function test_search_returns_search_list_response(): void
    {
        YoutubeDataApi::fake([
            YoutubeSearchResult::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $response = $youtube->search->listSearch('snippet', ['q' => 'test query']);

        $this->assertInstanceOf(SearchListResponse::class, $response);
        $this->assertCount(1, $response->getItems());
        $this->assertSame('Fake Search Result', $response->getItems()[0]->getSnippet()->getTitle());
    }

    public function test_list_playlists_returns_playlist_list_response(): void
    {
        YoutubeDataApi::fake([
            YoutubePlaylist::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $response = $youtube->playlists->listPlaylists('snippet', ['channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw']);

        $this->assertInstanceOf(PlaylistListResponse::class, $response);
        $this->assertCount(1, $response->getItems());
        $this->assertSame('Fake Playlist', $response->getItems()[0]->getSnippet()->getTitle());
    }

    public function test_empty_response_returns_no_items(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::empty(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $response = $youtube->videos->listVideos('snippet', ['id' => 'nonexistent']);

        $this->assertInstanceOf(VideoListResponse::class, $response);
        $this->assertEmpty($response->getItems());
    }

    public function test_error_response_throws_google_service_exception(): void
    {
        YoutubeDataApi::fake([
            ErrorResponse::notFound(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());

        $this->expectException(GoogleServiceException::class);

        $youtube->videos->listVideos('snippet', ['id' => 'nonexistent']);
    }

    public function test_quota_exceeded_throws_google_service_exception(): void
    {
        YoutubeDataApi::fake([
            ErrorResponse::quotaExceeded(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());

        $this->expectException(GoogleServiceException::class);

        $youtube->videos->listVideos('snippet', ['id' => 'any']);
    }

    public function test_prevent_stray_requests_throws_on_unqueued_call(): void
    {
        $fake = YoutubeDataApi::fake([]);
        $fake->preventStrayRequests();

        $youtube = new YouTube(YoutubeDataApi::client());

        $this->expectException(StrayRequestException::class);
        $this->expectExceptionMessage('Unexpected request');

        $youtube->videos->listVideos('snippet', ['id' => 'any']);
    }

    public function test_request_history_tracks_calls(): void
    {
        $fake = YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $youtube->videos->listVideos('snippet', ['id' => 'dQw4w9WgXcQ']);

        $fake->getRequestHistory()->assertSentCount(1);
        $fake->getRequestHistory()->assertListedVideos();
    }

    public function test_static_assertion_proxies(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $youtube->videos->listVideos('snippet', ['id' => 'dQw4w9WgXcQ']);

        YoutubeDataApi::assertSentCount(1);
        YoutubeDataApi::assertListedVideos();
    }

    public function test_assert_sent_with_callback(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());
        $youtube->videos->listVideos('snippet', ['id' => 'dQw4w9WgXcQ']);

        YoutubeDataApi::assertSent(function (RequestInterface $request): bool {
            return str_contains($request->getUri()->getPath(), '/youtube/v3/videos');
        });
    }

    public function test_assert_nothing_sent(): void
    {
        YoutubeDataApi::fake([]);

        YoutubeDataApi::assertNothingSent();
    }

    public function test_multiple_queued_responses(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
            YoutubeChannel::list(),
        ]);

        $youtube = new YouTube(YoutubeDataApi::client());

        $videoResponse = $youtube->videos->listVideos('snippet', ['id' => 'dQw4w9WgXcQ']);
        $this->assertInstanceOf(VideoListResponse::class, $videoResponse);

        $channelResponse = $youtube->channels->listChannels('snippet', ['id' => 'UCuAXFkgsw1L7xaCfnd5JJOw']);
        $this->assertInstanceOf(ChannelListResponse::class, $channelResponse);

        YoutubeDataApi::assertSentCount(2);
    }

    public function test_youtube_helper_returns_configured_service(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::list(),
        ]);

        $youtube = YoutubeDataApi::youtube();

        $this->assertInstanceOf(YouTube::class, $youtube);

        $response = $youtube->videos->listVideos('snippet', ['id' => 'dQw4w9WgXcQ']);
        $this->assertInstanceOf(VideoListResponse::class, $response);
    }
}
