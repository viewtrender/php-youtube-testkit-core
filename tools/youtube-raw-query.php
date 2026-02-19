#!/usr/bin/env php
<?php
/**
 * YouTube Data API Raw Query Tool
 * 
 * Makes raw HTTP requests to the YouTube Data API and returns unmodified JSON responses.
 * Useful for capturing exact API payloads for test fixtures.
 * 
 * Usage:
 *   php youtube-raw-query.php <resource> [options]
 * 
 * Examples:
 *   php youtube-raw-query.php videos --id=dQw4w9WgXcQ --part=snippet,statistics
 *   php youtube-raw-query.php channels --forUsername=Google --part=snippet
 *   php youtube-raw-query.php search --q=php --part=snippet --maxResults=5
 *   php youtube-raw-query.php playlists --id=PLxxxxxx --part=snippet,contentDetails
 * 
 *   # OAuth (authenticated) requests:
 *   php youtube-raw-query.php subscriptions --mine=true --auth
 *   php youtube-raw-query.php activities --mine=true --auth
 * 
 * Environment:
 *   YOUTUBE_API_KEY       - Required for unauthenticated requests
 *   YOUTUBE_CLIENT_ID     - Required for --auth (OAuth)
 *   YOUTUBE_CLIENT_SECRET - Required for --auth (OAuth)
 * 
 * Options:
 *   --save=<filename>  Save response to file (in fixtures/ directory)
 *   --pretty           Pretty print JSON output (default: true)
 *   --raw              Output raw JSON without pretty printing
 *   --auth             Use OAuth instead of API key (requires token setup)
 */

declare(strict_types=1);

const API_BASE = 'https://www.googleapis.com/youtube/v3/';
const TOKEN_FILE = __DIR__ . '/.youtube-oauth-token';

const RESOURCES = [
    'activities',
    'captions',
    'channels',
    'channelSections',
    'comments',
    'commentThreads',
    'i18nLanguages',
    'i18nRegions',
    'members',
    'membershipsLevels',
    'playlists',
    'playlistItems',
    'search',
    'subscriptions',
    'thumbnails',
    'videoAbuseReportReasons',
    'videoCategories',
    'videos',
    'watermarks',
];

function main(array $argv): int
{
    if (count($argv) < 2) {
        printUsage();
        return 1;
    }
    
    $resource = $argv[1];
    
    if ($resource === '--help' || $resource === '-h') {
        printUsage();
        return 0;
    }
    
    if ($resource === '--list') {
        echo "Available resources:\n";
        foreach (RESOURCES as $r) {
            echo "  - {$r}\n";
        }
        return 0;
    }
    
    // Parse options early to check for --auth
    $options = parseOptions(array_slice($argv, 2));
    $useAuth = isset($options['_auth']);
    $saveFile = $options['_save'] ?? null;
    $pretty = !isset($options['_raw']);
    unset($options['_save'], $options['_raw'], $options['_pretty'], $options['_auth']);
    
    // Get credentials based on auth mode
    if ($useAuth) {
        $clientId = getenv('YOUTUBE_CLIENT_ID');
        $clientSecret = getenv('YOUTUBE_CLIENT_SECRET');
        
        if (!$clientId || !$clientSecret) {
            fwrite(STDERR, "Error: YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET required for --auth.\n");
            return 1;
        }
        
        if (!file_exists(TOKEN_FILE)) {
            fwrite(STDERR, "Error: No OAuth token found. Run youtube-oauth-setup.php first.\n");
            return 1;
        }
        
        $tokenData = json_decode(file_get_contents(TOKEN_FILE), true);
        $accessToken = refreshAccessToken($clientId, $clientSecret, $tokenData['refresh_token']);
        
        if (!$accessToken) {
            fwrite(STDERR, "Error: Failed to refresh OAuth token. Run youtube-oauth-setup.php again.\n");
            return 1;
        }
    } else {
        $apiKey = getenv('YOUTUBE_API_KEY');
        
        if (!$apiKey) {
            fwrite(STDERR, "Error: YOUTUBE_API_KEY environment variable is required.\n");
            fwrite(STDERR, "Set it with: export YOUTUBE_API_KEY=your_api_key\n");
            fwrite(STDERR, "Or use --auth for OAuth authentication.\n");
            return 1;
        }
    }
    
    if (!in_array($resource, RESOURCES, true)) {
        fwrite(STDERR, "Error: Unknown resource '{$resource}'.\n");
        fwrite(STDERR, "Use --list to see available resources.\n");
        return 1;
    }
    
    // Ensure part is specified
    if (!isset($options['part'])) {
        $options['part'] = getDefaultPart($resource);
    }
    
    // Add API key if not using OAuth
    if (!$useAuth) {
        $options['key'] = $apiKey;
    }
    
    // Build URL
    $url = API_BASE . $resource . '?' . http_build_query($options);
    
    // Make request
    $headers = ['Accept: application/json'];
    if ($useAuth) {
        $headers[] = 'Authorization: Bearer ' . $accessToken;
    }
    $response = makeRequest($url, $headers);
    
    if ($response === false) {
        fwrite(STDERR, "Error: Failed to fetch from API.\n");
        return 1;
    }
    
    // Check for API errors
    $data = json_decode($response, true);
    if (isset($data['error'])) {
        fwrite(STDERR, "API Error: {$data['error']['message']}\n");
        fwrite(STDERR, "Code: {$data['error']['code']}\n");
        if (isset($data['error']['errors'])) {
            foreach ($data['error']['errors'] as $err) {
                fwrite(STDERR, "  - {$err['reason']}: {$err['message']}\n");
            }
        }
        return 1;
    }
    
    // Format output
    $output = $pretty 
        ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        : $response;
    
    // Save to file if requested
    if ($saveFile) {
        $fixturesDir = __DIR__ . '/../src/Fixtures/youtube/';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0755, true);
        }
        $filePath = $fixturesDir . $saveFile;
        if (!str_ends_with($filePath, '.json')) {
            $filePath .= '.json';
        }
        file_put_contents($filePath, $output . "\n");
        fwrite(STDERR, "Saved to: {$filePath}\n");
    }
    
    echo $output . "\n";
    return 0;
}

function parseOptions(array $args): array
{
    $options = [];
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--')) {
            $arg = substr($arg, 2);
            if (str_contains($arg, '=')) {
                [$key, $value] = explode('=', $arg, 2);
                // Handle special options
                if ($key === 'save') {
                    $options['_save'] = $value;
                } elseif ($key === 'raw') {
                    $options['_raw'] = true;
                } elseif ($key === 'pretty') {
                    $options['_pretty'] = true;
                } elseif ($key === 'auth') {
                    $options['_auth'] = true;
                } else {
                    $options[$key] = $value;
                }
            } else {
                if ($arg === 'raw') {
                    $options['_raw'] = true;
                } elseif ($arg === 'pretty') {
                    $options['_pretty'] = true;
                } elseif ($arg === 'auth') {
                    $options['_auth'] = true;
                } else {
                    $options[$arg] = true;
                }
            }
        }
    }
    return $options;
}

function getDefaultPart(string $resource): string
{
    return match($resource) {
        'videos' => 'snippet,contentDetails,statistics,status',
        'channels' => 'snippet,contentDetails,statistics,status,brandingSettings',
        'playlists' => 'snippet,contentDetails,status',
        'playlistItems' => 'snippet,contentDetails,status',
        'search' => 'snippet',
        'subscriptions' => 'snippet,contentDetails',
        'activities' => 'snippet,contentDetails',
        'captions' => 'snippet',
        'channelSections' => 'snippet,contentDetails',
        'comments' => 'snippet',
        'commentThreads' => 'snippet,replies',
        'i18nLanguages' => 'snippet',
        'i18nRegions' => 'snippet',
        'members' => 'snippet',
        'membershipsLevels' => 'snippet',
        'videoAbuseReportReasons' => 'snippet',
        'videoCategories' => 'snippet',
        default => 'snippet',
    };
}

function makeRequest(string $url, array $headers = []): string|false
{
    if (empty($headers)) {
        $headers = ['Accept: application/json'];
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        fwrite(STDERR, "cURL Error: {$error}\n");
        return false;
    }
    
    return $response;
}

function refreshAccessToken(string $clientId, string $clientSecret, string $refreshToken): ?string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://oauth2.googleapis.com/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function printUsage(): void
{
    echo <<<USAGE
YouTube Data API Raw Query Tool

Usage:
  php youtube-raw-query.php <resource> [options]
  php youtube-raw-query.php --list         List available resources
  php youtube-raw-query.php --help         Show this help

Examples:
  php youtube-raw-query.php videos --id=dQw4w9WgXcQ --part=snippet,statistics
  php youtube-raw-query.php channels --forUsername=Google --part=snippet
  php youtube-raw-query.php search --q=php --part=snippet --maxResults=5
  php youtube-raw-query.php videoCategories --regionCode=US
  php youtube-raw-query.php i18nLanguages
  php youtube-raw-query.php i18nRegions

  # Save response to fixtures:
  php youtube-raw-query.php videos --id=dQw4w9WgXcQ --save=example-video

Environment:
  YOUTUBE_API_KEY    Required. Your YouTube Data API v3 key.
                     Get one at: https://console.cloud.google.com/apis/credentials

Options:
  --part=<parts>     Comma-separated list of resource parts to include
  --save=<filename>  Save response to src/Fixtures/youtube/<filename>.json
  --raw              Output raw JSON without pretty printing
  --pretty           Pretty print JSON output (default)

Resource-specific parameters vary. See YouTube Data API docs:
  https://developers.google.com/youtube/v3/docs

USAGE;
}

exit(main($argv));
