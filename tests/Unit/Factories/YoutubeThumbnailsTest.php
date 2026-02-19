<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeThumbnails;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeThumbnailsTest extends TestCase
{
    public function test_set_returns_fake_response(): void
    {
        $response = YoutubeThumbnails::set();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_set_has_correct_structure(): void
    {
        $body = json_decode(YoutubeThumbnails::set()->body, true);

        $this->assertSame('youtube#thumbnailSetResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_thumbnail_has_all_sizes(): void
    {
        $body = json_decode(YoutubeThumbnails::set()->body, true);
        $thumbnail = $body['items'][0];

        $this->assertArrayHasKey('default', $thumbnail);
        $this->assertArrayHasKey('medium', $thumbnail);
        $this->assertArrayHasKey('high', $thumbnail);
        $this->assertArrayHasKey('standard', $thumbnail);
        $this->assertArrayHasKey('maxres', $thumbnail);
    }

    public function test_thumbnail_sizes_have_url_width_height(): void
    {
        $body = json_decode(YoutubeThumbnails::set()->body, true);
        $thumbnail = $body['items'][0];

        foreach (['default', 'medium', 'high', 'standard', 'maxres'] as $size) {
            $this->assertArrayHasKey('url', $thumbnail[$size]);
            $this->assertArrayHasKey('width', $thumbnail[$size]);
            $this->assertArrayHasKey('height', $thumbnail[$size]);
        }
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeThumbnails::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_thumbnail_with_overrides(): void
    {
        $thumbnail = YoutubeThumbnails::thumbnail([
            'default' => ['url' => 'https://example.com/custom-default.jpg'],
            'maxres' => ['width' => 1920, 'height' => 1080],
        ]);

        $this->assertSame('https://example.com/custom-default.jpg', $thumbnail['default']['url']);
        $this->assertSame(1920, $thumbnail['maxres']['width']);
        $this->assertSame(1080, $thumbnail['maxres']['height']);
        // Verify other fields are preserved
        $this->assertArrayHasKey('medium', $thumbnail);
        $this->assertArrayHasKey('high', $thumbnail);
    }

    public function test_set_with_thumbnails(): void
    {
        $response = YoutubeThumbnails::setWithThumbnails([
            ['default' => ['url' => 'https://example.com/thumb1.jpg']],
            ['default' => ['url' => 'https://example.com/thumb2.jpg']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('https://example.com/thumb1.jpg', $body['items'][0]['default']['url']);
        $this->assertSame('https://example.com/thumb2.jpg', $body['items'][1]['default']['url']);
    }

    public function test_set_with_top_level_overrides(): void
    {
        $response = YoutubeThumbnails::set(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }
}
