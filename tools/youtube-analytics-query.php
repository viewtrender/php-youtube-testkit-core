#!/usr/bin/env php
<?php
/**
 * YouTube Analytics API Raw Query Tool
 *
 * Uses the Google PHP SDK to query the YouTube Analytics API (v2) and return
 * raw JSON responses. Useful for capturing exact API payloads for test fixtures.
 *
 * Usage:
 *   php youtube-analytics-query.php --metrics=views,estimatedMinutesWatched [options]
 *
 * Examples:
 *   # Channel overview (no dimensions)
 *   php youtube-analytics-query.php \
 *       --metrics=views,estimatedMinutesWatched,averageViewDuration,subscribersGained,subscribersLost,likes,dislikes,shares,comments
 *
 *   # Daily metrics
 *   php youtube-analytics-query.php \
 *       --dimensions=day \
 *       --metrics=views,estimatedMinutesWatched,subscribersGained,subscribersLost \
 *       --sort=day
 *
 *   # Traffic sources
 *   php youtube-analytics-query.php \
 *       --dimensions=insightTrafficSourceType \
 *       --metrics=views,estimatedMinutesWatched \
 *       --sort=-views
 *
 *   # Playback locations
 *   php youtube-analytics-query.php \
 *       --dimensions=insightPlaybackLocationType \
 *       --metrics=views,estimatedMinutesWatched \
 *       --sort=-views
 *
 *   # Demographics
 *   php youtube-analytics-query.php \
 *       --dimensions=ageGroup,gender \
 *       --metrics=viewerPercentage \
 *       --sort=gender,ageGroup
 *
 *   # Sharing service
 *   php youtube-analytics-query.php \
 *       --dimensions=sharingService \
 *       --metrics=shares \
 *       --sort=-shares
 *
 *   # Operating system
 *   php youtube-analytics-query.php \
 *       --dimensions=operatingSystem \
 *       --metrics=views,estimatedMinutesWatched \
 *       --sort=-views
 *
 *   # Audience retention (single video)
 *   php youtube-analytics-query.php \
 *       --dimensions=elapsedVideoTimeRatio \
 *       --metrics=audienceWatchRatio,relativeRetentionPerformance \
 *       --filters=video==VIDEO_ID;audienceType==ORGANIC
 *
 *   # Save directly as fixture
 *   php youtube-analytics-query.php \
 *       --dimensions=insightPlaybackLocationType \
 *       --metrics=views,estimatedMinutesWatched \
 *       --save=playback-locations
 *
 * Presets (shorthand for common reports):
 *   php youtube-analytics-query.php --preset=playback-locations
 *   php youtube-analytics-query.php --preset=playback-location-detail --filters=video==VIDEO_ID
 *   php youtube-analytics-query.php --preset=operating-systems
 *   php youtube-analytics-query.php --preset=sharing-service
 *   php youtube-analytics-query.php --preset=audience-retention --filters=video==VIDEO_ID
 *
 * Environment:
 *   YOUTUBE_CLIENT_ID     - OAuth Client ID
 *   YOUTUBE_CLIENT_SECRET - OAuth Client Secret
 *
 * The tool reuses the OAuth token from youtube-oauth-setup.php.
 * Run that first if you haven't authenticated yet.
 *
 * Options:
 *   --ids=<value>          Channel identifier (default: channel==MINE)
 *   --startDate=<date>     Start date YYYY-MM-DD (default: 90 days ago)
 *   --endDate=<date>       End date YYYY-MM-DD (default: yesterday)
 *   --dimensions=<dims>    Comma-separated dimensions
 *   --metrics=<metrics>    Comma-separated metrics (required unless using --preset)
 *   --filters=<filters>    Semicolon-separated filters
 *   --sort=<sort>          Sort order
 *   --maxResults=<n>       Max rows to return
 *   --save=<filename>      Save to src/Fixtures/analytics/<filename>.json
 *   --preset=<name>        Use a predefined report configuration
 *   --raw                  Output raw JSON without pretty printing
 *   --list-presets         List available presets
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Google\Client;
use Google\Service\YouTubeAnalytics;

const TOKEN_FILE = __DIR__ . '/.youtube-oauth-token';

const PRESETS = [
    'channel-overview' => [
        'description' => 'Aggregate channel stats (no dimensions)',
        'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,subscribersGained,subscribersLost,likes,dislikes,shares,comments',
    ],
    'daily-metrics' => [
        'description' => 'Daily user activity',
        'dimensions' => 'day',
        'metrics' => 'views,estimatedMinutesWatched,subscribersGained,subscribersLost',
        'sort' => 'day',
    ],
    'top-videos' => [
        'description' => 'Top videos by views',
        'dimensions' => 'video',
        'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,likes,comments',
        'sort' => '-views',
        'maxResults' => 10,
    ],
    'traffic-sources' => [
        'description' => 'Views by traffic source type',
        'dimensions' => 'insightTrafficSourceType',
        'metrics' => 'engagedViews,views,estimatedMinutesWatched,videoThumbnailImpressions,videoThumbnailImpressionsClickRate',
        'sort' => '-views',
    ],
    'traffic-source-detail' => [
        'description' => 'Top traffic source details (requires insightTrafficSourceType filter)',
        'dimensions' => 'insightTrafficSourceDetail',
        'metrics' => 'engagedViews,views,estimatedMinutesWatched,videoThumbnailImpressions,videoThumbnailImpressionsClickRate',
        'sort' => '-views',
        'maxResults' => 25,
        'note' => 'Add --filters=insightTrafficSourceType==YT_SEARCH (or EXT_URL, RELATED_VIDEO, etc.)',
    ],
    'playback-locations' => [
        'description' => 'Views by playback location type',
        'dimensions' => 'insightPlaybackLocationType',
        'metrics' => 'engagedViews,views,estimatedMinutesWatched',
        'sort' => '-views',
    ],
    'playback-location-detail' => [
        'description' => 'Top embedded players (requires video filter)',
        'dimensions' => 'insightPlaybackLocationDetail',
        'metrics' => 'engagedViews,views,estimatedMinutesWatched',
        'filters' => 'insightPlaybackLocationType==EMBEDDED',
        'sort' => '-views',
        'maxResults' => 25,
        'note' => 'Add --filters=video==VIDEO_ID;insightPlaybackLocationType==EMBEDDED',
    ],
    'operating-systems' => [
        'description' => 'Views by operating system',
        'dimensions' => 'operatingSystem',
        'metrics' => 'views,estimatedMinutesWatched,engagedViews',
        'sort' => '-views',
    ],
    'device-types' => [
        'description' => 'Views by device type',
        'dimensions' => 'deviceType',
        'metrics' => 'engagedViews,views,estimatedMinutesWatched,videoThumbnailImpressions,videoThumbnailImpressionsClickRate',
        'sort' => '-views',
    ],
    'device-os-combo' => [
        'description' => 'Views by device type + operating system',
        'dimensions' => 'deviceType,operatingSystem',
        'metrics' => 'engagedViews,views,estimatedMinutesWatched',
        'sort' => '-views',
    ],
    'demographics' => [
        'description' => 'Viewer demographics by age and gender',
        'dimensions' => 'ageGroup,gender',
        'metrics' => 'viewerPercentage',
        'sort' => 'gender,ageGroup',
    ],
    'geography' => [
        'description' => 'Views by country',
        'dimensions' => 'country',
        'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,subscribersGained,subscribersLost',
        'sort' => '-views',
    ],
    'sharing-service' => [
        'description' => 'Shares by sharing service',
        'dimensions' => 'sharingService',
        'metrics' => 'shares',
        'sort' => '-shares',
    ],
    'audience-retention' => [
        'description' => 'Audience retention curve (requires video filter)',
        'dimensions' => 'elapsedVideoTimeRatio',
        'metrics' => 'audienceWatchRatio,relativeRetentionPerformance',
        'note' => 'Requires --filters=video==VIDEO_ID (single video only). Optionally add ;audienceType==ORGANIC',
    ],
    'video-types' => [
        'description' => 'Views by content type (VOD, Shorts, Live)',
        'dimensions' => 'creatorContentType',
        'metrics' => 'views,estimatedMinutesWatched,likes,shares',
        'sort' => '-views',
    ],
];

function main(array $argv): int
{
    $options = parseOptions(array_slice($argv, 1));

    if (isset($options['_help'])) {
        printUsage();
        return 0;
    }

    if (isset($options['_list-presets'])) {
        listPresets();
        return 0;
    }

    // --- Resolve preset ---
    $preset = $options['_preset'] ?? null;
    if ($preset) {
        if (!isset(PRESETS[$preset])) {
            fwrite(STDERR, "Error: Unknown preset '{$preset}'.\n");
            fwrite(STDERR, "Use --list-presets to see available presets.\n");
            return 1;
        }
        $p = PRESETS[$preset];
        if (isset($p['note'])) {
            fwrite(STDERR, "Note: {$p['note']}\n\n");
        }
    }

    // --- Build query params (CLI overrides preset) ---
    $ids       = $options['ids']        ?? 'channel==MINE';
    $startDate = $options['startDate']  ?? date('Y-m-d', strtotime('-90 days'));
    $endDate   = $options['endDate']    ?? date('Y-m-d', strtotime('-1 day'));
    $dimensions = $options['dimensions'] ?? ($preset ? ($p['dimensions'] ?? '') : '');
    $metrics    = $options['metrics']    ?? ($preset ? $p['metrics'] : '');
    $filters    = mergeFilters($options['filters'] ?? null, $preset ? ($p['filters'] ?? null) : null);
    $sort       = $options['sort']       ?? ($preset ? ($p['sort'] ?? '') : '');
    $maxResults = $options['maxResults'] ?? ($preset ? ($p['maxResults'] ?? null) : null);
    $saveFile   = $options['_save']      ?? null;
    $pretty     = !isset($options['_raw']);

    if (!$metrics) {
        fwrite(STDERR, "Error: --metrics is required (or use --preset).\n");
        return 1;
    }

    // --- Authenticate ---
    $clientId = getenv('YOUTUBE_CLIENT_ID');
    $clientSecret = getenv('YOUTUBE_CLIENT_SECRET');

    if (!$clientId || !$clientSecret) {
        fwrite(STDERR, "Error: YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET required.\n");
        return 1;
    }

    if (!file_exists(TOKEN_FILE)) {
        fwrite(STDERR, "Error: No OAuth token found. Run youtube-oauth-setup.php first.\n");
        return 1;
    }

    $tokenData = json_decode(file_get_contents(TOKEN_FILE), true);
    if (!$tokenData || !isset($tokenData['refresh_token'])) {
        fwrite(STDERR, "Error: Invalid token file. Run youtube-oauth-setup.php again.\n");
        return 1;
    }

    // --- Set up Google Client ---
    $client = new Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setAccessType('offline');
    $client->setScopes([
        'https://www.googleapis.com/auth/yt-analytics.readonly',
        'https://www.googleapis.com/auth/youtube.readonly',
    ]);

    $client->setAccessToken($tokenData);

    if ($client->isAccessTokenExpired()) {
        fwrite(STDERR, "Refreshing access token...\n");
        $client->fetchAccessTokenWithRefreshToken($tokenData['refresh_token']);
        $newToken = $client->getAccessToken();
        if (!$newToken || isset($newToken['error'])) {
            fwrite(STDERR, "Error: Failed to refresh token. Run youtube-oauth-setup.php again.\n");
            return 1;
        }
        // Preserve refresh token
        $newToken['refresh_token'] = $tokenData['refresh_token'];
        file_put_contents(TOKEN_FILE, json_encode($newToken, JSON_PRETTY_PRINT));
        chmod(TOKEN_FILE, 0600);
    }

    // --- Query Analytics API ---
    $analyticsService = new YouTubeAnalytics($client);

    $queryParams = [
        'ids' => $ids,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'metrics' => $metrics,
    ];

    if ($dimensions) {
        $queryParams['dimensions'] = $dimensions;
    }
    if ($filters) {
        $queryParams['filters'] = $filters;
    }
    if ($sort) {
        $queryParams['sort'] = $sort;
    }
    if ($maxResults !== null) {
        $queryParams['maxResults'] = (int)$maxResults;
    }

    // Show the query being made
    fwrite(STDERR, "Query:\n");
    foreach ($queryParams as $k => $v) {
        fwrite(STDERR, "  {$k}: {$v}\n");
    }
    fwrite(STDERR, "\n");

    try {
        /** @var \Google\Service\YouTubeAnalytics\QueryResponse $response */
        $response = $analyticsService->reports->query($queryParams);
    } catch (\Google\Service\Exception $e) {
        fwrite(STDERR, "API Error (HTTP {$e->getCode()}):\n");
        foreach ($e->getErrors() as $err) {
            fwrite(STDERR, "  [{$err['reason']}] {$err['message']}\n");
        }
        return 1;
    } catch (\Exception $e) {
        fwrite(STDERR, "Error: {$e->getMessage()}\n");
        return 1;
    }

    // --- Build clean JSON output matching fixture format ---
    $columnHeaders = [];
    foreach ($response->getColumnHeaders() as $header) {
        $columnHeaders[] = [
            'name' => $header->getName(),
            'columnType' => $header->getColumnType(),
            'dataType' => $header->getDataType(),
        ];
    }

    $output = [
        'kind' => $response->getKind(),
        'columnHeaders' => $columnHeaders,
        'rows' => $response->getRows() ?? [],
    ];

    $json = $pretty
        ? json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        : json_encode($output);

    // --- Save as fixture if requested ---
    if ($saveFile) {
        $fixturesDir = dirname(__DIR__) . '/src/Fixtures/analytics/';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0755, true);
        }
        $filePath = $fixturesDir . $saveFile;
        if (!str_ends_with($filePath, '.json')) {
            $filePath .= '.json';
        }
        file_put_contents($filePath, $json . "\n");
        fwrite(STDERR, "✓ Saved to: {$filePath}\n");
    }

    $rowCount = count($output['rows']);
    $colCount = count($output['columnHeaders']);
    fwrite(STDERR, "✓ {$rowCount} rows × {$colCount} columns\n\n");

    echo $json . "\n";
    return 0;
}

function parseOptions(array $args): array
{
    $options = [];
    foreach ($args as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['_help'] = true;
        } elseif ($arg === '--list-presets') {
            $options['_list-presets'] = true;
        } elseif ($arg === '--raw') {
            $options['_raw'] = true;
        } elseif (str_starts_with($arg, '--')) {
            $arg = substr($arg, 2);
            if (str_contains($arg, '=')) {
                [$key, $value] = explode('=', $arg, 2);
                if ($key === 'save') {
                    $options['_save'] = $value;
                } elseif ($key === 'preset') {
                    $options['_preset'] = $value;
                } else {
                    $options[$key] = $value;
                }
            }
        }
    }
    return $options;
}

/**
 * Merge CLI filters with preset filters. CLI filters take precedence for the
 * same key; otherwise they're concatenated with semicolons.
 */
function mergeFilters(?string $cliFilters, ?string $presetFilters): string
{
    if (!$cliFilters && !$presetFilters) {
        return '';
    }
    if (!$presetFilters) {
        return $cliFilters;
    }
    if (!$cliFilters) {
        return $presetFilters;
    }

    // Parse both into key=>value maps
    $parse = function (string $raw): array {
        $map = [];
        foreach (explode(';', $raw) as $part) {
            if (str_contains($part, '==')) {
                [$k, $v] = explode('==', $part, 2);
                $map[$k] = $v;
            }
        }
        return $map;
    };

    $merged = array_merge($parse($presetFilters), $parse($cliFilters));
    $parts = [];
    foreach ($merged as $k => $v) {
        $parts[] = "{$k}=={$v}";
    }
    return implode(';', $parts);
}

function listPresets(): void
{
    echo "Available presets:\n\n";
    foreach (PRESETS as $name => $config) {
        echo "  {$name}\n";
        echo "    {$config['description']}\n";
        if (isset($config['dimensions'])) {
            echo "    dimensions: {$config['dimensions']}\n";
        }
        echo "    metrics: {$config['metrics']}\n";
        if (isset($config['note'])) {
            echo "    ⚠  {$config['note']}\n";
        }
        echo "\n";
    }
}

function printUsage(): void
{
    echo <<<'USAGE'
YouTube Analytics API Raw Query Tool

Uses the Google PHP SDK to query youtubeAnalytics.reports.query and return
raw JSON responses suitable for use as test fixtures.

Usage:
  php youtube-analytics-query.php --metrics=<metrics> [options]
  php youtube-analytics-query.php --preset=<name> [options]
  php youtube-analytics-query.php --list-presets
  php youtube-analytics-query.php --help

Options:
  --ids=<value>          Channel identifier (default: channel==MINE)
  --startDate=<date>     Start date YYYY-MM-DD (default: 90 days ago)
  --endDate=<date>       End date YYYY-MM-DD (default: yesterday)
  --dimensions=<dims>    Comma-separated dimensions
  --metrics=<metrics>    Comma-separated metrics
  --filters=<filters>    Semicolon-separated filters (e.g. country==US;video==ID)
  --sort=<sort>          Sort order (prefix - for descending)
  --maxResults=<n>       Max rows to return
  --preset=<name>        Use a predefined report configuration
  --save=<filename>      Save to src/Fixtures/analytics/<filename>.json
  --raw                  Output without pretty printing

Examples:
  # Capture playback locations fixture
  php youtube-analytics-query.php --preset=playback-locations --save=playback-locations

  # Capture audience retention for a specific video
  php youtube-analytics-query.php --preset=audience-retention \
      --filters=video==dQw4w9WgXcQ --save=audience-retention

  # Custom query with save
  php youtube-analytics-query.php \
      --dimensions=operatingSystem \
      --metrics=views,estimatedMinutesWatched \
      --sort=-views \
      --save=operating-systems

Environment:
  YOUTUBE_CLIENT_ID     - Required (OAuth)
  YOUTUBE_CLIENT_SECRET - Required (OAuth)

  Run youtube-oauth-setup.php first to authenticate.

USAGE;
}

exit(main($argv));
