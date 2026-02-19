<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeComments;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeCommentsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeComments::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeComments::list()->body, true);

        $this->assertSame('youtube#commentThreadListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('pageInfo', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_list_comment_thread_has_snippet_and_replies(): void
    {
        $body = json_decode(YoutubeComments::list()->body, true);
        $thread = $body['items'][0];

        $this->assertSame('youtube#commentThread', $thread['kind']);
        $this->assertArrayHasKey('snippet', $thread);
        $this->assertArrayHasKey('replies', $thread);
        $this->assertArrayHasKey('topLevelComment', $thread['snippet']);
        $this->assertSame('This is a fake comment for testing purposes.', $thread['snippet']['topLevelComment']['snippet']['textDisplay']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeComments::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_comment_returns_single_comment_thread_array(): void
    {
        $comment = YoutubeComments::comment();

        $this->assertSame('youtube#commentThread', $comment['kind']);
        $this->assertSame('UgzfakeCommentThread123', $comment['id']);
    }

    public function test_comment_with_overrides(): void
    {
        $comment = YoutubeComments::comment([
            'id' => 'custom-thread-id',
            'snippet' => [
                'videoId' => 'custom-video-id',
                'topLevelComment' => [
                    'snippet' => [
                        'textDisplay' => 'Custom comment text',
                    ],
                ],
            ],
        ]);

        $this->assertSame('custom-thread-id', $comment['id']);
        $this->assertSame('custom-video-id', $comment['snippet']['videoId']);
        $this->assertSame('Custom comment text', $comment['snippet']['topLevelComment']['snippet']['textDisplay']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('channelId', $comment['snippet']);
        $this->assertArrayHasKey('authorDisplayName', $comment['snippet']['topLevelComment']['snippet']);
    }

    public function test_list_with_comments(): void
    {
        $response = YoutubeComments::listWithComments([
            ['id' => 'thread-1', 'snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'First comment']]]],
            ['id' => 'thread-2', 'snippet' => ['topLevelComment' => ['snippet' => ['textDisplay' => 'Second comment']]]],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('thread-1', $body['items'][0]['id']);
        $this->assertSame('thread-2', $body['items'][1]['id']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_comment_preserves_reply_structure(): void
    {
        $comment = YoutubeComments::comment();

        $this->assertArrayHasKey('replies', $comment);
        $this->assertArrayHasKey('comments', $comment['replies']);
        $this->assertNotEmpty($comment['replies']['comments']);
        $this->assertSame('youtube#comment', $comment['replies']['comments'][0]['kind']);
    }
}
