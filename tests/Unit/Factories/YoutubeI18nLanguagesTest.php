<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeI18nLanguages;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeI18nLanguagesTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeI18nLanguages::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeI18nLanguages::list()->body, true);

        $this->assertSame('youtube#i18nLanguageListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_language_has_snippet_with_hl_and_name(): void
    {
        $body = json_decode(YoutubeI18nLanguages::list()->body, true);
        $language = $body['items'][0];

        $this->assertSame('youtube#i18nLanguage', $language['kind']);
        $this->assertArrayHasKey('snippet', $language);
        $this->assertSame('en', $language['snippet']['hl']);
        $this->assertSame('English', $language['snippet']['name']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeI18nLanguages::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_language_returns_single_language_array(): void
    {
        $language = YoutubeI18nLanguages::language();

        $this->assertSame('youtube#i18nLanguage', $language['kind']);
        $this->assertSame('en', $language['id']);
    }

    public function test_language_with_overrides(): void
    {
        $language = YoutubeI18nLanguages::language([
            'id' => 'pt',
            'snippet' => ['hl' => 'pt', 'name' => 'Portuguese'],
        ]);

        $this->assertSame('pt', $language['id']);
        $this->assertSame('pt', $language['snippet']['hl']);
        $this->assertSame('Portuguese', $language['snippet']['name']);
    }

    public function test_list_with_languages(): void
    {
        $response = YoutubeI18nLanguages::listWithLanguages([
            ['id' => 'ko', 'snippet' => ['hl' => 'ko', 'name' => 'Korean']],
            ['id' => 'zh', 'snippet' => ['hl' => 'zh', 'name' => 'Chinese']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('ko', $body['items'][0]['id']);
        $this->assertSame('Korean', $body['items'][0]['snippet']['name']);
        $this->assertSame('zh', $body['items'][1]['id']);
    }

    public function test_list_with_overrides(): void
    {
        $response = YoutubeI18nLanguages::list(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }
}
