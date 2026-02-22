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

    public function test_list_with_videos_does_not_include_next_page_token(): void
    {
        $response = YoutubeVideo::listWithVideos([
            ['id' => 'vid-1'],
            ['id' => 'vid-2'],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_empty_does_not_include_next_page_token(): void
    {
        $body = json_decode(YoutubeVideo::empty()->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeVideo::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubeVideo::paginated(pages: 3, perPage: 2);

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
        $responses = YoutubeVideo::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubeVideo::pages([
            [
                ['snippet' => ['title' => 'Page 1 Video 1']],
                ['snippet' => ['title' => 'Page 1 Video 2']],
            ],
            [
                ['snippet' => ['title' => 'Page 2 Video 1']],
            ],
        ]);

        $this->assertCount(2, $responses);

        // First page
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('Page 1 Video 1', $body1['items'][0]['snippet']['title']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);
        $this->assertSame(3, $body1['pageInfo']['totalResults']);

        // Second page (last) has no nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(1, $body2['items']);
        $this->assertSame('Page 2 Video 1', $body2['items'][0]['snippet']['title']);
        $this->assertArrayNotHasKey('nextPageToken', $body2);
    }

    public function test_pages_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeVideo::pages([
            [['snippet' => ['title' => 'Only Video']]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
