<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Integration;

use Google\Service\YouTubeReporting;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Tests\TestCase;

class YoutubeReportingApiTest extends TestCase
{
    public function test_fake_mechanism_works(): void
    {
        YoutubeReportingApi::fake([
            ReportingReportType::list(),
            ReportingJob::create()
        ]);

        $service = YoutubeReportingApi::reporting();
        $this->assertInstanceOf(YouTubeReporting::class, $service);

        YoutubeReportingApi::assertSentCount(0); // No requests sent yet

        // Reset for clean state
        YoutubeReportingApi::reset();
    }

    public function test_can_get_client(): void
    {
        YoutubeReportingApi::fake();

        $client = YoutubeReportingApi::client();
        
        $this->assertNotNull($client);
        $this->assertNotNull($client->getAccessToken());

        YoutubeReportingApi::reset();
    }

    public function test_assertions_work(): void
    {
        YoutubeReportingApi::fake();
        
        YoutubeReportingApi::assertNothingSent();
        YoutubeReportingApi::assertSentCount(0);

        YoutubeReportingApi::reset();
    }

    protected function tearDown(): void
    {
        YoutubeReportingApi::reset();
        parent::tearDown();
    }
}