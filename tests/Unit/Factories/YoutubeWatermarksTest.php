<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeWatermarks;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeWatermarksTest extends TestCase
{
    public function test_set_returns_fake_response(): void
    {
        $response = YoutubeWatermarks::set();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_set_has_correct_structure(): void
    {
        $body = json_decode(YoutubeWatermarks::set()->body, true);

        $this->assertArrayHasKey('timing', $body);
        $this->assertArrayHasKey('position', $body);
        $this->assertArrayHasKey('targetChannelId', $body);
    }

    public function test_set_has_timing_properties(): void
    {
        $body = json_decode(YoutubeWatermarks::set()->body, true);

        $this->assertSame('offsetFromStart', $body['timing']['type']);
        $this->assertArrayHasKey('offsetMs', $body['timing']);
        $this->assertArrayHasKey('durationMs', $body['timing']);
    }

    public function test_set_has_position_properties(): void
    {
        $body = json_decode(YoutubeWatermarks::set()->body, true);

        $this->assertSame('corner', $body['position']['type']);
        $this->assertSame('topRight', $body['position']['cornerPosition']);
    }

    public function test_empty_returns_empty_response(): void
    {
        $body = json_decode(YoutubeWatermarks::empty()->body, true);

        $this->assertEmpty($body);
    }

    public function test_watermark_with_overrides(): void
    {
        $watermark = YoutubeWatermarks::watermark([
            'targetChannelId' => 'custom-channel-id',
            'timing' => ['offsetMs' => '5000'],
        ]);

        $this->assertSame('custom-channel-id', $watermark['targetChannelId']);
        $this->assertSame('5000', $watermark['timing']['offsetMs']);
        $this->assertSame('offsetFromStart', $watermark['timing']['type']);
    }

    public function test_set_with_watermark(): void
    {
        $response = YoutubeWatermarks::setWithWatermark([
            'targetChannelId' => 'UC123456789',
            'position' => ['cornerPosition' => 'bottomLeft'],
        ]);

        $body = json_decode($response->body, true);

        $this->assertSame('UC123456789', $body['targetChannelId']);
        $this->assertSame('bottomLeft', $body['position']['cornerPosition']);
        $this->assertSame('corner', $body['position']['type']);
    }

    public function test_set_with_overrides(): void
    {
        $response = YoutubeWatermarks::set([
            'imageUrl' => 'https://example.com/custom-watermark.png',
        ]);

        $body = json_decode($response->body, true);

        $this->assertSame('https://example.com/custom-watermark.png', $body['imageUrl']);
    }
}
