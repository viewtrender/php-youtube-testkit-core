<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeI18nRegions;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeI18nRegionsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeI18nRegions::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeI18nRegions::list()->body, true);

        $this->assertSame('youtube#i18nRegionListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_region_has_snippet(): void
    {
        $body = json_decode(YoutubeI18nRegions::list()->body, true);
        $region = $body['items'][0];

        $this->assertSame('youtube#i18nRegion', $region['kind']);
        $this->assertArrayHasKey('snippet', $region);
        $this->assertSame('US', $region['id']);
        $this->assertSame('US', $region['snippet']['gl']);
        $this->assertSame('United States', $region['snippet']['name']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeI18nRegions::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_region_with_overrides(): void
    {
        $region = YoutubeI18nRegions::region([
            'id' => 'DE',
            'snippet' => ['gl' => 'DE', 'name' => 'Germany'],
        ]);

        $this->assertSame('DE', $region['id']);
        $this->assertSame('DE', $region['snippet']['gl']);
        $this->assertSame('Germany', $region['snippet']['name']);
    }

    public function test_list_with_regions(): void
    {
        $response = YoutubeI18nRegions::listWithRegions([
            ['id' => 'FR', 'snippet' => ['gl' => 'FR', 'name' => 'France']],
            ['id' => 'JP', 'snippet' => ['gl' => 'JP', 'name' => 'Japan']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('FR', $body['items'][0]['id']);
        $this->assertSame('JP', $body['items'][1]['id']);
    }

    public function test_list_accepts_top_level_overrides(): void
    {
        $response = YoutubeI18nRegions::list(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }
}
