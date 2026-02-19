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

    public function test_subscription_snippet_has_channel_title(): void
    {
        $subscription = YoutubeSubscriptions::subscription();

        $this->assertArrayHasKey('channelTitle', $subscription['snippet']);
        $this->assertSame('Your Channel Name', $subscription['snippet']['channelTitle']);
    }
}
