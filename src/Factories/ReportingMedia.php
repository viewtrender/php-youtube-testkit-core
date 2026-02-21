<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Factories;

use Viewtrender\Youtube\Responses\FakeResponse;

class ReportingMedia
{
    public static function channelBasicCsv(array $overrides = []): FakeResponse
    {
        $csvData = self::loadCsvFixture('channel-basic-data.csv');
        
        if (!empty($overrides)) {
            $csvData = self::customizeCsvData($csvData, $overrides);
        }

        return FakeResponse::make($csvData)
            ->status(200)
            ->header('Content-Type', 'text/csv');
    }

    public static function customCsv(string $csvContent): FakeResponse
    {
        return FakeResponse::make($csvContent)
            ->status(200)
            ->header('Content-Type', 'text/csv');
    }

    public static function downloadResponse(string $reportId = 'fake_report'): FakeResponse
    {
        // Default to channel basic CSV for any download request
        return self::channelBasicCsv();
    }

    private static function loadCsvFixture(string $filename): string
    {
        static $fixtures = [];

        if (!isset($fixtures[$filename])) {
            $path = dirname(__DIR__) . '/Fixtures/reporting/' . $filename;
            $fixtures[$filename] = file_get_contents($path);
        }

        return $fixtures[$filename];
    }

    private static function customizeCsvData(string $csvData, array $overrides): string
    {
        $lines = explode("\n", $csvData);
        $header = array_shift($lines);
        
        $customizedLines = [];
        foreach ($lines as $line) {
            if (trim($line)) {
                $customizedLines[] = $line;
            }
        }

        // Add any custom rows from overrides
        if (isset($overrides['rows'])) {
            foreach ($overrides['rows'] as $row) {
                $customizedLines[] = implode(',', $row);
            }
        }

        return $header . "\n" . implode("\n", $customizedLines);
    }
}