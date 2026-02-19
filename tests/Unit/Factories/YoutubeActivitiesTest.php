<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeActivities;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeActivitiesTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeActivities::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeActivities::list()->body, true);

        $this->assertSame('youtube#activityListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_activity_has_snippet_and_content_details(): void
    {
        $body = json_decode(YoutubeActivities::list()->body, true);
        $activity = $body['items'][0];

        $this->assertSame('youtube#activity', $activity['kind']);
        $this->assertArrayHasKey('snippet', $activity);
        $this->assertArrayHasKey('contentDetails', $activity);
        $this->assertSame('upload', $activity['snippet']['type']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeActivities::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_activity_with_overrides(): void
    {
        $activity = YoutubeActivities::activity([
            'id' => 'custom-activity-id',
            'snippet' => ['title' => 'My Activity', 'type' => 'like'],
        ]);

        $this->assertSame('custom-activity-id', $activity['id']);
        $this->assertSame('My Activity', $activity['snippet']['title']);
        $this->assertSame('like', $activity['snippet']['type']);
        $this->assertArrayHasKey('channelId', $activity['snippet']);
    }

    public function test_list_with_activities(): void
    {
        $response = YoutubeActivities::listWithActivities([
            ['id' => 'act-1', 'snippet' => ['title' => 'Activity One']],
            ['id' => 'act-2', 'snippet' => ['title' => 'Activity Two']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('act-1', $body['items'][0]['id']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_activity_preserves_content_details_on_merge(): void
    {
        $activity = YoutubeActivities::activity([
            'contentDetails' => [
                'upload' => ['videoId' => 'custom-video-id'],
            ],
        ]);

        $this->assertSame('custom-video-id', $activity['contentDetails']['upload']['videoId']);
    }

    public function test_activity_has_all_thumbnail_sizes(): void
    {
        $body = json_decode(YoutubeActivities::list()->body, true);
        $thumbnails = $body['items'][0]['snippet']['thumbnails'];

        $this->assertArrayHasKey('default', $thumbnails);
        $this->assertArrayHasKey('medium', $thumbnails);
        $this->assertArrayHasKey('high', $thumbnails);
        $this->assertArrayHasKey('standard', $thumbnails);
        $this->assertArrayHasKey('maxres', $thumbnails);

        foreach (['default', 'medium', 'high', 'standard', 'maxres'] as $size) {
            $this->assertArrayHasKey('url', $thumbnails[$size]);
            $this->assertArrayHasKey('width', $thumbnails[$size]);
            $this->assertArrayHasKey('height', $thumbnails[$size]);
        }
    }

    public function test_list_has_pagination_token(): void
    {
        $body = json_decode(YoutubeActivities::list()->body, true);

        $this->assertArrayHasKey('nextPageToken', $body);
    }
}
