<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeVideoCategories;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeVideoCategoriesTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeVideoCategories::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeVideoCategories::list()->body, true);

        $this->assertSame('youtube#videoCategoryListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_list_video_category_has_snippet(): void
    {
        $body = json_decode(YoutubeVideoCategories::list()->body, true);
        $category = $body['items'][0];

        $this->assertSame('youtube#videoCategory', $category['kind']);
        $this->assertArrayHasKey('snippet', $category);
        $this->assertSame('Film & Animation', $category['snippet']['title']);
        $this->assertTrue($category['snippet']['assignable']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeVideoCategories::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_video_category_returns_single_category_array(): void
    {
        $category = YoutubeVideoCategories::videoCategory();

        $this->assertSame('youtube#videoCategory', $category['kind']);
        $this->assertSame('1', $category['id']);
    }

    public function test_video_category_with_overrides(): void
    {
        $category = YoutubeVideoCategories::videoCategory([
            'id' => '99',
            'snippet' => ['title' => 'Custom Category'],
        ]);

        $this->assertSame('99', $category['id']);
        $this->assertSame('Custom Category', $category['snippet']['title']);
        // Non-overridden fields should still be present
        $this->assertArrayHasKey('assignable', $category['snippet']);
    }

    public function test_list_with_video_categories(): void
    {
        $response = YoutubeVideoCategories::listWithVideoCategories([
            ['id' => '10', 'snippet' => ['title' => 'Music']],
            ['id' => '20', 'snippet' => ['title' => 'Gaming']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('10', $body['items'][0]['id']);
        $this->assertSame('20', $body['items'][1]['id']);
        $this->assertSame('Music', $body['items'][0]['snippet']['title']);
        $this->assertSame('Gaming', $body['items'][1]['snippet']['title']);
    }
}
