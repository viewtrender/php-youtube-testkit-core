<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeSubscriptions;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeSubscriptionsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeSubscriptions::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeSubscriptions::list()->body, true);

        $this->assertSame('youtube#subscriptionListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_subscription_has_snippet_and_content_details(): void
    {
        $body = json_decode(YoutubeSubscriptions::list()->body, true);
        $subscription = $body['items'][0];

        $this->assertSame('youtube#subscription', $subscription['kind']);
        $this->assertArrayHasKey('snippet', $subscription);
        $this->assertArrayHasKey('contentDetails', $subscription);
        $this->assertSame('Subscribed Channel', $subscription['snippet']['title']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeSubscriptions::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_subscription_with_overrides(): void
    {
        $subscription = YoutubeSubscriptions::subscription([
            'id' => 'custom-subscription-id',
            'snippet' => ['title' => 'My Favorite Channel'],
        ]);

        $this->assertSame('custom-subscription-id', $subscription['id']);
        $this->assertSame('My Favorite Channel', $subscription['snippet']['title']);
        $this->assertArrayHasKey('resourceId', $subscription['snippet']);
    }

    public function test_list_with_subscriptions(): void
    {
        $response = YoutubeSubscriptions::listWithSubscriptions([
            ['id' => 'sub-1', 'snippet' => ['title' => 'Channel One']],
            ['id' => 'sub-2', 'snippet' => ['title' => 'Channel Two']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('sub-1', $body['items'][0]['id']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_subscription_resource_id_contains_channel(): void
    {
        $subscription = YoutubeSubscriptions::subscription();

        $this->assertSame('youtube#channel', $subscription['snippet']['resourceId']['kind']);
        $this->assertArrayHasKey('channelId', $subscription['snippet']['resourceId']);
    }

    public function test_subscription_has_subscriber_snippet(): void
    {
        $body = json_decode(YoutubeSubscriptions::list()->body, true);
        $subscription = $body['items'][0];

        $this->assertArrayHasKey('subscriberSnippet', $subscription);
        $this->assertArrayHasKey('title', $subscription['subscriberSnippet']);
        $this->assertArrayHasKey('channelId', $subscription['subscriberSnippet']);
    }

    public function test_list_with_subscriptions_does_not_include_next_page_token(): void
    {
        $response = YoutubeSubscriptions::listWithSubscriptions([
            ['id' => 'sub-1', 'snippet' => ['title' => 'Channel One']],
            ['id' => 'sub-2', 'snippet' => ['title' => 'Channel Two']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_empty_does_not_include_next_page_token(): void
    {
        $body = json_decode(YoutubeSubscriptions::empty()->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeSubscriptions::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubeSubscriptions::paginated(pages: 3, perPage: 2);

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
        $responses = YoutubeSubscriptions::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubeSubscriptions::pages([
            [
                ['snippet' => ['title' => 'Page 1 Channel 1']],
                ['snippet' => ['title' => 'Page 1 Channel 2']],
            ],
            [
                ['snippet' => ['title' => 'Page 2 Channel 1']],
            ],
        ]);

        $this->assertCount(2, $responses);

        // First page
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('Page 1 Channel 1', $body1['items'][0]['snippet']['title']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);
        $this->assertSame(3, $body1['pageInfo']['totalResults']);

        // Second page (last) has no nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(1, $body2['items']);
        $this->assertSame('Page 2 Channel 1', $body2['items'][0]['snippet']['title']);
        $this->assertArrayNotHasKey('nextPageToken', $body2);
    }

    public function test_pages_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeSubscriptions::pages([
            [['snippet' => ['title' => 'Only Channel']]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
