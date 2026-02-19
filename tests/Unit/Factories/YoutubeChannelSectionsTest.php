<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeChannelSections;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeChannelSectionsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeChannelSections::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeChannelSections::list()->body, true);

        $this->assertSame('youtube#channelSectionListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_channel_section_has_snippet_and_content_details(): void
    {
        $body = json_decode(YoutubeChannelSections::list()->body, true);
        $section = $body['items'][0];

        $this->assertSame('youtube#channelSection', $section['kind']);
        $this->assertArrayHasKey('snippet', $section);
        $this->assertArrayHasKey('contentDetails', $section);
        $this->assertSame('singlePlaylist', $section['snippet']['type']);
        $this->assertSame('Featured Videos', $section['snippet']['title']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeChannelSections::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_channel_section_with_overrides(): void
    {
        $section = YoutubeChannelSections::channelSection([
            'id' => 'custom-section-id',
            'snippet' => ['title' => 'My Custom Section', 'type' => 'multiplePlaylists'],
        ]);

        $this->assertSame('custom-section-id', $section['id']);
        $this->assertSame('My Custom Section', $section['snippet']['title']);
        $this->assertSame('multiplePlaylists', $section['snippet']['type']);
        $this->assertArrayHasKey('channelId', $section['snippet']);
    }

    public function test_list_with_channel_sections(): void
    {
        $response = YoutubeChannelSections::listWithChannelSections([
            ['id' => 'section-1', 'snippet' => ['title' => 'Section One', 'position' => 0]],
            ['id' => 'section-2', 'snippet' => ['title' => 'Section Two', 'position' => 1]],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('section-1', $body['items'][0]['id']);
        $this->assertSame('Section One', $body['items'][0]['snippet']['title']);
        $this->assertSame('section-2', $body['items'][1]['id']);
    }

    public function test_channel_section_content_details_has_playlists(): void
    {
        $section = YoutubeChannelSections::channelSection();

        $this->assertArrayHasKey('playlists', $section['contentDetails']);
        $this->assertIsArray($section['contentDetails']['playlists']);
    }

    public function test_list_with_overrides(): void
    {
        $response = YoutubeChannelSections::list(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }
}
