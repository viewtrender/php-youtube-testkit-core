<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeMembers;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeMembersTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeMembers::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeMembers::list()->body, true);

        $this->assertSame('youtube#memberListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_member_has_snippet_with_member_details(): void
    {
        $body = json_decode(YoutubeMembers::list()->body, true);
        $member = $body['items'][0];

        $this->assertSame('youtube#member', $member['kind']);
        $this->assertArrayHasKey('snippet', $member);
        $this->assertArrayHasKey('memberDetails', $member['snippet']);
        $this->assertArrayHasKey('membershipsDetails', $member['snippet']);
        $this->assertSame('Fake Member', $member['snippet']['memberDetails']['displayName']);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeMembers::empty()->body, true);

        $this->assertEmpty($body['items']);
        $this->assertSame(0, $body['pageInfo']['totalResults']);
    }

    public function test_member_with_overrides(): void
    {
        $member = YoutubeMembers::member([
            'snippet' => [
                'memberDetails' => ['displayName' => 'Custom Member'],
                'membershipsDetails' => ['highestAccessibleLevelDisplayName' => 'Gold Member'],
            ],
        ]);

        $this->assertSame('Custom Member', $member['snippet']['memberDetails']['displayName']);
        $this->assertSame('Gold Member', $member['snippet']['membershipsDetails']['highestAccessibleLevelDisplayName']);
        $this->assertArrayHasKey('channelId', $member['snippet']['memberDetails']);
    }

    public function test_list_with_members(): void
    {
        $response = YoutubeMembers::listWithMembers([
            ['snippet' => ['memberDetails' => ['displayName' => 'Member One', 'channelId' => 'UC111']]],
            ['snippet' => ['memberDetails' => ['displayName' => 'Member Two', 'channelId' => 'UC222']]],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('Member One', $body['items'][0]['snippet']['memberDetails']['displayName']);
        $this->assertSame('UC222', $body['items'][1]['snippet']['memberDetails']['channelId']);
        $this->assertSame(2, $body['pageInfo']['totalResults']);
    }

    public function test_member_preserves_nested_structure(): void
    {
        $member = YoutubeMembers::member([
            'snippet' => [
                'membershipsDetails' => [
                    'membershipsDuration' => ['memberTotalDurationMonths' => 24],
                ],
            ],
        ]);

        $this->assertSame(24, $member['snippet']['membershipsDetails']['membershipsDuration']['memberTotalDurationMonths']);
        $this->assertArrayHasKey('memberSince', $member['snippet']['membershipsDetails']['membershipsDuration']);
    }
}
