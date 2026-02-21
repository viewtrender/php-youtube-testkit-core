<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use JsonException;
use Viewtrender\Youtube\Responses\FakeResponse;

class ReportingJob
{
    /**
     * @throws JsonException
     */
    public static function list(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('jobs-list.json');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * @throws JsonException
     */
    public static function create(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('job.json');
        $job = array_merge($fixture, $overrides);
        
        // Generate unique ID if not provided
        if (!isset($overrides['id'])) {
            $job['id'] = 'fake_job_' . uniqid();
        }
        
        // Set current time if not provided
        if (!isset($overrides['createTime'])) {
            $job['createTime'] = date('c');
        }

        return FakeResponse::make($job);
    }

    /**
     * @throws JsonException
     */
    public static function get(array $overrides = []): FakeResponse
    {
        $fixture = self::loadFixture('job.json');

        return FakeResponse::make(array_merge($fixture, $overrides));
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @throws JsonException
     */
    public static function listWithJobs(array $jobs): FakeResponse
    {
        $fixture = self::loadFixture('jobs-list.json');
        $fixture['jobs'] = $jobs;

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    public static function empty(): FakeResponse
    {
        $fixture = self::loadFixture('jobs-list.json');
        $fixture['jobs'] = [];

        return FakeResponse::make($fixture);
    }

    /**
     * @throws JsonException
     */
    private static function loadFixture(string $filename): array
    {
        static $fixtures = [];

        if (!isset($fixtures[$filename])) {
            $path = dirname(__DIR__) . '/Fixtures/reporting/' . $filename;
            $fixtures[$filename] = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return $fixtures[$filename];
    }
}