<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeMembershipsLevels;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeMembershipsLevelsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeMembershipsLevels::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeMembershipsLevels::list()->body, true);

        $this->assertSame('youtube#membershipsLevelListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_memberships_level_has_snippet_and_level_details(): void
    {
        $body = json_decode(YoutubeMembershipsLevels::list()->body, true);
        $level = $body['items'][0];

        $this->assertSame('youtube#membershipsLevel', $level['kind']);
        $this->assertArrayHasKey('snippet', $level);
        $this->assertArrayHasKey('levelDetails', $level['snippet']);
        $this->assertSame('Bronze Supporter', $level['snippet']['levelDetails']['displayName']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeMembershipsLevels::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_memberships_level_with_overrides(): void
    {
        $level = YoutubeMembershipsLevels::membershipsLevel([
            'id' => 'custom-level-id',
            'snippet' => ['levelDetails' => ['displayName' => 'Custom Level']],
        ]);

        $this->assertSame('custom-level-id', $level['id']);
        $this->assertSame('Custom Level', $level['snippet']['levelDetails']['displayName']);
        $this->assertArrayHasKey('creatorChannelId', $level['snippet']);
    }

    public function test_list_with_memberships_levels(): void
    {
        $response = YoutubeMembershipsLevels::listWithMembershipsLevels([
            ['id' => 'level-1', 'snippet' => ['levelDetails' => ['displayName' => 'Level One']]],
            ['id' => 'level-2', 'snippet' => ['levelDetails' => ['displayName' => 'Level Two']]],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('level-1', $body['items'][0]['id']);
        $this->assertSame('Level One', $body['items'][0]['snippet']['levelDetails']['displayName']);
    }
}
