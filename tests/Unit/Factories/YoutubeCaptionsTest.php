<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeCaptions;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeCaptionsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeCaptions::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeCaptions::list()->body, true);

        $this->assertSame('youtube#captionListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('etag', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_list_caption_has_all_snippet_fields(): void
    {
        $body = json_decode(YoutubeCaptions::list()->body, true);
        $caption = $body['items'][0];

        $this->assertSame('youtube#caption', $caption['kind']);
        $this->assertArrayHasKey('snippet', $caption);

        $snippet = $caption['snippet'];
        $this->assertArrayHasKey('videoId', $snippet);
        $this->assertArrayHasKey('lastUpdated', $snippet);
        $this->assertArrayHasKey('trackKind', $snippet);
        $this->assertArrayHasKey('language', $snippet);
        $this->assertArrayHasKey('name', $snippet);
        $this->assertArrayHasKey('audioTrackType', $snippet);
        $this->assertArrayHasKey('isCC', $snippet);
        $this->assertArrayHasKey('isLarge', $snippet);
        $this->assertArrayHasKey('isEasyReader', $snippet);
        $this->assertArrayHasKey('isDraft', $snippet);
        $this->assertArrayHasKey('isAutoSynced', $snippet);
        $this->assertArrayHasKey('status', $snippet);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeCaptions::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame('youtube#captionListResponse', $body['kind']);
    }

    public function test_caption_returns_single_caption_array(): void
    {
        $caption = YoutubeCaptions::caption();

        $this->assertSame('youtube#caption', $caption['kind']);
        $this->assertSame('caption-sample-1', $caption['id']);
    }

    public function test_caption_with_overrides(): void
    {
        $caption = YoutubeCaptions::caption([
            'id' => 'custom-caption-id',
            'snippet' => ['language' => 'fr', 'name' => 'French'],
        ]);

        $this->assertSame('custom-caption-id', $caption['id']);
        $this->assertSame('fr', $caption['snippet']['language']);
        $this->assertSame('French', $caption['snippet']['name']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('videoId', $caption['snippet']);
        $this->assertArrayHasKey('trackKind', $caption['snippet']);
    }

    public function test_list_with_captions(): void
    {
        $response = YoutubeCaptions::listWithCaptions([
            ['id' => 'cap-1', 'snippet' => ['language' => 'en']],
            ['id' => 'cap-2', 'snippet' => ['language' => 'de']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('cap-1', $body['items'][0]['id']);
        $this->assertSame('cap-2', $body['items'][1]['id']);
        $this->assertSame('en', $body['items'][0]['snippet']['language']);
        $this->assertSame('de', $body['items'][1]['snippet']['language']);
    }

    public function test_list_with_overrides(): void
    {
        $response = YoutubeCaptions::list(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }
}
