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

    public function test_list_with_results_does_not_include_next_page_token(): void
    {
        $response = YoutubeSearchResult::listWithResults([
            ['snippet' => ['title' => 'Result One']],
            ['snippet' => ['title' => 'Result Two']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_empty_does_not_include_next_page_token(): void
    {
        $body = json_decode(YoutubeSearchResult::empty()->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeSearchResult::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubeSearchResult::paginated(pages: 3, perPage: 2);

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
        $responses = YoutubeSearchResult::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubeSearchResult::pages([
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
        $responses = YoutubeSearchResult::pages([
            [['snippet' => ['title' => 'Only Item']]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
