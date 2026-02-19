<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\YoutubeVideoAbuseReportReasons;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeVideoAbuseReportReasonsTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = YoutubeVideoAbuseReportReasons::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(YoutubeVideoAbuseReportReasons::list()->body, true);

        $this->assertSame('youtube#videoAbuseReportReasonListResponse', $body['kind']);
        $this->assertArrayHasKey('items', $body);
        $this->assertNotEmpty($body['items']);
    }

    public function test_reason_has_snippet_with_label(): void
    {
        $body = json_decode(YoutubeVideoAbuseReportReasons::list()->body, true);
        $reason = $body['items'][0];

        $this->assertSame('youtube#videoAbuseReportReason', $reason['kind']);
        $this->assertArrayHasKey('snippet', $reason);
        $this->assertArrayHasKey('label', $reason['snippet']);
        $this->assertSame('Spam or misleading', $reason['snippet']['label']);
    }

    public function test_reason_has_secondary_reasons(): void
    {
        $body = json_decode(YoutubeVideoAbuseReportReasons::list()->body, true);
        $reason = $body['items'][0];

        $this->assertArrayHasKey('secondaryReasons', $reason['snippet']);
        $this->assertNotEmpty($reason['snippet']['secondaryReasons']);
        $this->assertArrayHasKey('id', $reason['snippet']['secondaryReasons'][0]);
        $this->assertArrayHasKey('label', $reason['snippet']['secondaryReasons'][0]);
    }

    public function test_empty_returns_no_items(): void
    {
        $body = json_decode(YoutubeVideoAbuseReportReasons::empty()->body, true);

        $this->assertEmpty($body['items']);
    }

    public function test_reason_with_overrides(): void
    {
        $reason = YoutubeVideoAbuseReportReasons::reason([
            'id' => 'CUSTOM',
            'snippet' => ['label' => 'Custom Reason'],
        ]);

        $this->assertSame('CUSTOM', $reason['id']);
        $this->assertSame('Custom Reason', $reason['snippet']['label']);
        $this->assertArrayHasKey('secondaryReasons', $reason['snippet']);
    }

    public function test_reason_with_custom_secondary_reasons(): void
    {
        $reason = YoutubeVideoAbuseReportReasons::reason([
            'id' => 'X',
            'snippet' => [
                'label' => 'Test Reason',
                'secondaryReasons' => [
                    ['id' => 'X.1', 'label' => 'Sub-reason one'],
                    ['id' => 'X.2', 'label' => 'Sub-reason two'],
                ],
            ],
        ]);

        $this->assertSame('X', $reason['id']);
        $this->assertCount(2, $reason['snippet']['secondaryReasons']);
        $this->assertSame('X.1', $reason['snippet']['secondaryReasons'][0]['id']);
    }

    public function test_list_with_reasons(): void
    {
        $response = YoutubeVideoAbuseReportReasons::listWithReasons([
            ['id' => 'A', 'snippet' => ['label' => 'Reason A']],
            ['id' => 'B', 'snippet' => ['label' => 'Reason B']],
        ]);

        $body = json_decode($response->body, true);

        $this->assertCount(2, $body['items']);
        $this->assertSame('A', $body['items'][0]['id']);
        $this->assertSame('Reason A', $body['items'][0]['snippet']['label']);
        $this->assertSame('B', $body['items'][1]['id']);
        $this->assertSame('Reason B', $body['items'][1]['snippet']['label']);
    }

    public function test_list_with_overrides(): void
    {
        $response = YoutubeVideoAbuseReportReasons::list(['etag' => 'custom-etag']);

        $body = json_decode($response->body, true);

        $this->assertSame('custom-etag', $body['etag']);
    }
}
