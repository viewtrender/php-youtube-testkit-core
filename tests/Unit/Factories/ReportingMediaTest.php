<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\ReportingMedia;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class ReportingMediaTest extends TestCase
{
    public function test_channel_basic_csv_returns_fake_response(): void
    {
        $response = ReportingMedia::channelBasicCsv();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_channel_basic_csv_has_csv_content(): void
    {
        $response = ReportingMedia::channelBasicCsv();
        $csvData = $response->body;

        $this->assertStringContainsString('date,channel_id,views', $csvData);
        $this->assertStringContainsString('UCtest123', $csvData);
        $this->assertStringContainsString('1500', $csvData);
    }

    public function test_channel_basic_csv_has_correct_headers(): void
    {
        $response = ReportingMedia::channelBasicCsv();
        $headers = $response->getHeaders();

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringContainsString('text/csv', $headers['Content-Type']);
    }

    public function test_custom_csv(): void
    {
        $csvContent = "col1,col2\nval1,val2\n";
        $response = ReportingMedia::customCsv($csvContent);

        $this->assertSame($csvContent, $response->body);
        $this->assertStringContainsString('text/csv', $response->getHeaders()['Content-Type']);
    }

    public function test_download_response(): void
    {
        $response = ReportingMedia::downloadResponse('some_report_id');

        $this->assertInstanceOf(FakeResponse::class, $response);
        $this->assertStringContainsString('date,channel_id', $response->body);
    }
}