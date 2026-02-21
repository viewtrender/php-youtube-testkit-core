<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests\Unit\Factories;

use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Responses\FakeResponse;
use Viewtrender\Youtube\Tests\TestCase;

class ReportingJobTest extends TestCase
{
    public function test_list_returns_fake_response(): void
    {
        $response = ReportingJob::list();

        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    public function test_list_has_correct_structure(): void
    {
        $body = json_decode(ReportingJob::list()->body, true);

        $this->assertArrayHasKey('jobs', $body);
        $this->assertNotEmpty($body['jobs']);
        $this->assertArrayHasKey('nextPageToken', $body);
    }

    public function test_create_returns_job_with_id(): void
    {
        $body = json_decode(ReportingJob::create()->body, true);

        $this->assertArrayHasKey('id', $body);
        $this->assertStringStartsWith('fake_job_', $body['id']);
        $this->assertArrayHasKey('reportTypeId', $body);
        $this->assertArrayHasKey('name', $body);
        $this->assertArrayHasKey('createTime', $body);
    }

    public function test_create_with_overrides(): void
    {
        $body = json_decode(ReportingJob::create([
            'id' => 'custom_job_123',
            'reportTypeId' => 'custom_report_type',
            'name' => 'My Custom Job'
        ])->body, true);

        $this->assertSame('custom_job_123', $body['id']);
        $this->assertSame('custom_report_type', $body['reportTypeId']);
        $this->assertSame('My Custom Job', $body['name']);
    }

    public function test_empty_returns_no_jobs(): void
    {
        $body = json_decode(ReportingJob::empty()->body, true);

        $this->assertEmpty($body['jobs']);
    }

    public function test_list_with_jobs(): void
    {
        $jobs = [
            ['id' => 'job1', 'name' => 'Job One'],
            ['id' => 'job2', 'name' => 'Job Two'],
        ];

        $body = json_decode(ReportingJob::listWithJobs($jobs)->body, true);

        $this->assertCount(2, $body['jobs']);
        $this->assertSame('job1', $body['jobs'][0]['id']);
        $this->assertSame('Job One', $body['jobs'][0]['name']);
    }
}