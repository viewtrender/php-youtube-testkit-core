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

    public function test_empty_does_not_include_next_page_token(): void
    {
        $body = json_decode(YoutubeActivities::empty()->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_list_with_activities_does_not_include_next_page_token(): void
    {
        $response = YoutubeActivities::listWithActivities([
            ['id' => 'act-1'],
            ['id' => 'act-2'],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeActivities::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubeActivities::paginated(pages: 3, perPage: 2);

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
        $responses = YoutubeActivities::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubeActivities::pages([
            [
                ['snippet' => ['title' => 'Page 1 Activity 1']],
                ['snippet' => ['title' => 'Page 1 Activity 2']],
            ],
            [
                ['snippet' => ['title' => 'Page 2 Activity 1']],
            ],
        ]);

        $this->assertCount(2, $responses);

        // First page
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('Page 1 Activity 1', $body1['items'][0]['snippet']['title']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);
        $this->assertSame(3, $body1['pageInfo']['totalResults']);

        // Second page (last) has no nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(1, $body2['items']);
        $this->assertSame('Page 2 Activity 1', $body2['items'][0]['snippet']['title']);
        $this->assertArrayNotHasKey('nextPageToken', $body2);
    }

    public function test_pages_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeActivities::pages([
            [['snippet' => ['title' => 'Only Activity']]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
