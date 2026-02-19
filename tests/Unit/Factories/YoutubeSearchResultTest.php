<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeSearchResult;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeSearchResultTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeSearchResult::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeSearchResult::list()->body, true);

        $this->assertSame('youtube#searchListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_search_result_has_id_and_snippet(): void
    {
        $body = json_decode(YoutubeSearchResult::list()->body, true);
        $result = $body['items'][0];

        $this->assertSame('youtube#searchResult', $result['kind']);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('snippet', $result);
        $this->assertSame('youtube#video', $result['id']['kind']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeSearchResult::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_search_result_with_overrides(): void
    {
        $result = YoutubeSearchResult::searchResult([
            'id' => ['kind' => 'youtube#channel', 'channelId' => 'UC123'],
            'snippet' => ['title' => 'Custom Result'],
        ]);

        $this->assertSame('youtube#channel', $result['id']['kind']);
        $this->assertSame('Custom Result', $result['snippet']['title']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('channelId', $result['snippet']);
        $this->assertArrayHasKey('liveBroadcastContent', $result['snippet']);
    }

    public function test_list_with_overrides(): void
    {
        $response = YoutubeSearchResult::list(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }

    public function test_list_with_results(): void
    {
        $response = YoutubeSearchResult::listWithResults([
            ['snippet' => ['title' => 'Result One']],
            ['snippet' => ['title' => 'Result Two']],
            ['snippet' => ['title' => 'Result Three']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(3, $body['items']);
        $this->assertSame(3, $body['pageInfo']['totalResults']);
    }
}
