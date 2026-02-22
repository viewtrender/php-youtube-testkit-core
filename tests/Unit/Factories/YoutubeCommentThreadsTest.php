<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeCommentThreads;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeCommentThreadsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeCommentThreads::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeCommentThreads::list()->body, true);

        $this->assertSame('youtube#commentThreadListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('pageInfo', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_list_comment_thread_has_snippet_and_replies(): void
    {
        $body = json_decode(YoutubeCommentThreads::list()->body, true);
        $commentThread = $body['items'][0];

        $this->assertSame('youtube#commentThread', $commentThread['kind']);
        $this->assertArrayHasKey('snippet', $commentThread);
        $this->assertArrayHasKey('replies', $commentThread);
        $this->assertArrayHasKey('topLevelComment', $commentThread['snippet']);
        $this->assertSame('This is a fake comment for testing purposes.', $commentThread['snippet']['topLevelComment']['snippet']['textDisplay']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeCommentThreads::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_comment_thread_returns_single_comment_thread_array(): void
    {
        $commentThread = YoutubeCommentThreads::commentThread();

        $this->assertSame('youtube#commentThread', $commentThread['kind']);
        $this->assertSame('UgzHoKL6d7bF5HhvDXp4AaABAg', $commentThread['id']);
    }

    public function test_comment_thread_with_overrides(): void
    {
        $commentThread = YoutubeCommentThreads::commentThread([
            'id' => 'custom-thread-id',
            'snippet' => [
                'videoId' => 'custom-video-id',
                'topLevelComment' => [
                    'snippet' => ['textDisplay' => 'Custom comment text'],
                ],
            ],
        ]);

        $this->assertSame('custom-thread-id', $commentThread['id']);
        $this->assertSame('custom-video-id', $commentThread['snippet']['videoId']);
        $this->assertSame('Custom comment text', $commentThread['snippet']['topLevelComment']['snippet']['textDisplay']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('channelId', $commentThread['snippet']);
        $this->assertArrayHasKey('totalReplyCount', $commentThread['snippet']);
    }

    public function test_list_with_comment_threads(): void
    {
        $response = YoutubeCommentThreads::listWithCommentThreads([
            ['id' => 'thread-1', 'snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'First comment']]]],
            ['id' => 'thread-2', 'snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'Second comment']]]],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('thread-1', $body['items'][0]['id']);
        $this->assertSame('thread-2', $body['items'][1]['id']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_comment_thread_preserves_replies_structure(): void
    {
        $commentThread = YoutubeCommentThreads::commentThread();

        $this->assertArrayHasKey('replies', $commentThread);
        $this->assertArrayHasKey('comments', $commentThread['replies']);
        $this->assertNotEmpty($commentThread['replies']['comments']);
        $this->assertSame('youtube#comment', $commentThread['replies']['comments'][0]['kind']);
    }

    public function test_list_with_comment_threads_does_not_include_next_page_token(): void
    {
        $response = YoutubeCommentThreads::listWithCommentThreads([
            ['id' => 'thread-1'],
            ['id' => 'thread-2'],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_empty_does_not_include_next_page_token(): void
    {
        $body = json_decode(YoutubeCommentThreads::empty()->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeCommentThreads::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubeCommentThreads::paginated(pages: 3, perPage: 2);

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
        $responses = YoutubeCommentThreads::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubeCommentThreads::pages([
            [
                ['snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'Page 1 Thread 1']]]],
                ['snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'Page 1 Thread 2']]]],
            ],
            [
                ['snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'Page 2 Thread 1']]]],
            ],
        ]);

        $this->assertCount(2, $responses);

        // First page
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('Page 1 Thread 1', $body1['items'][0]['snippet']['topLevelComment']['snippet']['textDisplay']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);
        $this->assertSame(3, $body1['pageInfo']['totalResults']);

        // Second page (last) has no nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(1, $body2['items']);
        $this->assertSame('Page 2 Thread 1', $body2['items'][0]['snippet']['topLevelComment']['snippet']['textDisplay']);
        $this->assertArrayNotHasKey('nextPageToken', $body2);
    }

    public function test_pages_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeCommentThreads::pages([
            [['snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'Only Thread']]]]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
