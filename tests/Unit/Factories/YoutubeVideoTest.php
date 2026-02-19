<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeVideoTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeVideo::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeVideo::list()->body, true);

        $this->assertSame('youtube#videoListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('pageInfo', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_list_video_has_snippet_and_statistics(): void
    {
        $body = json_decode(YoutubeVideo::list()->body, true);
        $video = $body['items'][0];

        $this->assertSame('youtube#video', $video['kind']);
        $this->assertArrayHasKey('snippet', $video);
        $this->assertArrayHasKey('statistics', $video);
        $this->assertArrayHasKey('contentDetails', $video);
        $this->assertSame('Fake Video Title', $video['snippet']['title']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeVideo::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_video_returns_single_video_array(): void
    {
        $video = YoutubeVideo::video();

        $this->assertSame('youtube#video', $video['kind']);
        $this->assertSame('dQw4w9WgXcQ', $video['id']);
    }

    public function test_video_with_overrides(): void
    {
        $video = YoutubeVideo::video([
            'id' => 'custom-id',
            'snippet' => ['title' => 'Custom Title'],
        ]);

        $this->assertSame('custom-id', $video['id']);
        $this->assertSame('Custom Title', $video['snippet']['title']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('channelId', $video['snippet']);
    }

    public function test_list_with_videos(): void
    {
        $response = YoutubeVideo::listWithVideos([
            ['id' => 'vid-1', 'snippet' => ['title' => 'First']],
            ['id' => 'vid-2', 'snippet' => ['title' => 'Second']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('vid-1', $body['items'][0]['id']);
        $this->assertSame('vid-2', $body['items'][1]['id']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }
}
