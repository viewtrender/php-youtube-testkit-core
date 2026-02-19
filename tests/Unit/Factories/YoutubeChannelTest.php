<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeChannelTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeChannel::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeChannel::list()->body, true);

        $this->assertSame('youtube#channelListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_channel_has_snippet_and_statistics(): void
    {
        $body = json_decode(YoutubeChannel::list()->body, true);
        $channel = $body['items'][0];

        $this->assertSame('youtube#channel', $channel['kind']);
        $this->assertArrayHasKey('snippet', $channel);
        $this->assertArrayHasKey('statistics', $channel);
        $this->assertSame('Fake Channel', $channel['snippet']['title']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeChannel::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_channel_with_overrides(): void
    {
        $channel = YoutubeChannel::channel([
            'id' => 'custom-channel-id',
            'snippet' => ['title' => 'My Channel'],
        ]);

        $this->assertSame('custom-channel-id', $channel['id']);
        $this->assertSame('My Channel', $channel['snippet']['title']);
        $this->assertArrayHasKey('country', $channel['snippet']);
    }

    public function test_list_with_channels(): void
    {
        $response = YoutubeChannel::listWithChannels([
            ['id' => 'ch-1', 'snippet' => ['title' => 'Channel One']],
            ['id' => 'ch-2', 'snippet' => ['title' => 'Channel Two']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('ch-1', $body['items'][0]['id']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }
}
