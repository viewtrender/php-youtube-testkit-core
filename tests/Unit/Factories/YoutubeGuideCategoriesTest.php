<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeGuideCategories;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeGuideCategoriesTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeGuideCategories::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeGuideCategories::list()->body, true);

        $this->assertSame('youtube#guideCategoryListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_guide_category_has_snippet(): void
    {
        $body = json_decode(YoutubeGuideCategories::list()->body, true);
        $guideCategory = $body['items'][0];

        $this->assertSame('youtube#guideCategory', $guideCategory['kind']);
        $this->assertArrayHasKey('snippet', $guideCategory);
        $this->assertSame('Best of YouTube', $guideCategory['snippet']['title']);
        $this->assertArrayHasKey('channelId', $guideCategory['snippet']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeGuideCategories::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_guide_category_with_overrides(): void
    {
        $guideCategory = YoutubeGuideCategories::guideCategory([
            'id' => 'custom-guide-category-id',
            'snippet' => ['title' => 'Custom Category'],
        ]);

        $this->assertSame('custom-guide-category-id', $guideCategory['id']);
        $this->assertSame('Custom Category', $guideCategory['snippet']['title']);
        $this->assertArrayHasKey('channelId', $guideCategory['snippet']);
    }

    public function test_list_with_guide_categories(): void
    {
        $response = YoutubeGuideCategories::listWithGuideCategories([
            ['id' => 'gc-1', 'snippet' => ['title' => 'Category One']],
            ['id' => 'gc-2', 'snippet' => ['title' => 'Category Two']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('gc-1', $body['items'][0]['id']);
        $this->assertSame('Category One', $body['items'][0]['snippet']['title']);
        $this->assertSame('gc-2', $body['items'][1]['id']);
        $this->assertSame('Category Two', $body['items'][1]['snippet']['title']);
    }
}
