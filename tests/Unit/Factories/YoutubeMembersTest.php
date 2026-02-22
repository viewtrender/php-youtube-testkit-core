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

    public function test_list_with_members_does_not_include_next_page_token(): void
    {
        $response = YoutubeMembers::listWithMembers([
            ['snippet' => ['memberDetails' => ['displayName' => 'Member One']]],
            ['snippet' => ['memberDetails' => ['displayName' => 'Member Two']]],
        ]);

        $body = json_decode($response->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_empty_does_not_include_next_page_token(): void
    {
        $body = json_decode(YoutubeMembers::empty()->body, true);

        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeMembers::paginated(pages: 1, perPage: 3);

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(FakeResponse::class, $responses[0]);

        $body = json_decode($responses[0]->body, true);
        $this->assertCount(3, $body['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }

    public function test_paginated_multiple_pages_have_correct_tokens(): void
    {
        $responses = YoutubeMembers::paginated(pages: 3, perPage: 2);

        $this->assertCount(3, $responses);

        // First page has nextPageToken
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);

        // Second page has nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(2, $body2['items']);
        $this->assertSame('page_token_3', $body2['nextPageToken']);

        // Last page has no nextPageToken
        $body3 = json_decode($responses[2]->body, true);
        $this->assertCount(2, $body3['items']);
        $this->assertArrayNotHasKey('nextPageToken', $body3);
    }

    public function test_paginated_sets_correct_total_results(): void
    {
        $responses = YoutubeMembers::paginated(pages: 2, perPage: 5);

        $body = json_decode($responses[0]->body, true);
        $this->assertSame(10, $body['pageInfo']['totalResults']);
        $this->assertSame(5, $body['pageInfo']['resultsPerPage']);
    }

    public function test_pages_with_explicit_items(): void
    {
        $responses = YoutubeMembers::pages([
            [
                ['snippet' => ['memberDetails' => ['displayName' => 'Page 1 Member 1']]],
                ['snippet' => ['memberDetails' => ['displayName' => 'Page 1 Member 2']]],
            ],
            [
                ['snippet' => ['memberDetails' => ['displayName' => 'Page 2 Member 1']]],
            ],
        ]);

        $this->assertCount(2, $responses);

        // First page
        $body1 = json_decode($responses[0]->body, true);
        $this->assertCount(2, $body1['items']);
        $this->assertSame('Page 1 Member 1', $body1['items'][0]['snippet']['memberDetails']['displayName']);
        $this->assertSame('page_token_2', $body1['nextPageToken']);
        $this->assertSame(3, $body1['pageInfo']['totalResults']);

        // Second page (last) has no nextPageToken
        $body2 = json_decode($responses[1]->body, true);
        $this->assertCount(1, $body2['items']);
        $this->assertSame('Page 2 Member 1', $body2['items'][0]['snippet']['memberDetails']['displayName']);
        $this->assertArrayNotHasKey('nextPageToken', $body2);
    }

    public function test_pages_single_page_has_no_next_page_token(): void
    {
        $responses = YoutubeMembers::pages([
            [['snippet' => ['memberDetails' => ['displayName' => 'Only Member']]]],
        ]);

        $this->assertCount(1, $responses);
        $body = json_decode($responses[0]->body, true);
        $this->assertArrayNotHasKey('nextPageToken', $body);
    }
}
