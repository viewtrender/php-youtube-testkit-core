#!/usr/bin/env php
<?php
/**
 * YouTube OAuth Setup Tool
 * 
 * Gets OAuth tokens for authenticated YouTube API requests.
 * Uses localhost callback with built-in PHP server.
 * 
 * Usage:
 *   php youtube-oauth-setup.php
 * 
 * Environment:
 *   YOUTUBE_CLIENT_ID     - OAuth Client ID
 *   YOUTUBE_CLIENT_SECRET - OAuth Client Secret
 */

declare(strict_types=1);

const TOKEN_FILE = __DIR__ . '/.youtube-oauth-token';
const CALLBACK_PORT = 8765;
const REDIRECT_URI = 'http://localhost:' . CALLBACK_PORT . '/callback';

const SCOPES = [
    'https://www.googleapis.com/auth/youtube.readonly',
    'https://www.googleapis.com/auth/yt-analytics.readonly',
];

function main(): int
{
    $clientId = getenv('YOUTUBE_CLIENT_ID');
    $clientSecret = getenv('YOUTUBE_CLIENT_SECRET');
    
    if (!$clientId || !$clientSecret) {
        fwrite(STDERR, "Error: YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET environment variables required.\n");
        return 1;
    }
    
    // Check if we already have a valid token
    if (file_exists(TOKEN_FILE)) {
        $token = json_decode(file_get_contents(TOKEN_FILE), true);
        if ($token && isset($token['refresh_token'])) {
            echo "Existing token found. Testing...\n";
            $accessToken = refreshAccessToken($clientId, $clientSecret, $token['refresh_token']);
            if ($accessToken) {
                echo "✓ Token is valid!\n";
                return 0;
            }
            echo "Token expired. Re-authenticating...\n\n";
        }
    }
    
    // Generate auth URL
    $state = bin2hex(random_bytes(16));
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => REDIRECT_URI,
        'response_type' => 'code',
        'scope' => implode(' ', SCOPES),
        'access_type' => 'offline',
        'prompt' => 'consent',
        'state' => $state,
    ]);
    
    echo "=== YouTube OAuth Setup ===\n\n";
    echo "Starting local callback server on port " . CALLBACK_PORT . "...\n\n";
    
    // Create socket server
    $socket = @stream_socket_server('tcp://127.0.0.1:' . CALLBACK_PORT, $errno, $errstr);
    if (!$socket) {
        fwrite(STDERR, "Error: Could not start server: {$errstr}\n");
        fwrite(STDERR, "Try a different port or check if port " . CALLBACK_PORT . " is in use.\n");
        return 1;
    }
    
    echo "1. Open this URL in your browser:\n\n";
    echo "   {$authUrl}\n\n";
    echo "2. Sign in with the Google account that owns the test channel\n";
    echo "3. Authorize the application\n\n";
    echo "Waiting for callback...\n";
    
    // Wait for callback (timeout after 5 minutes)
    stream_set_timeout($socket, 300);
    $conn = @stream_socket_accept($socket, 300);
    
    if (!$conn) {
        fwrite(STDERR, "Error: Timeout waiting for OAuth callback.\n");
        fclose($socket);
        return 1;
    }
    
    // Read the HTTP request
    $request = '';
    while (($line = fgets($conn)) !== false) {
        $request .= $line;
        if (trim($line) === '') break;
    }
    
    // Parse the authorization code from the URL
    if (preg_match('/GET \/callback\?([^ ]+)/', $request, $matches)) {
        parse_str($matches[1], $params);
        
        if (isset($params['error'])) {
            $errorResponse = "HTTP/1.1 400 Bad Request\r\nContent-Type: text/html\r\n\r\n";
            $errorResponse .= "<h1>Authorization Failed</h1><p>Error: {$params['error']}</p>";
            fwrite($conn, $errorResponse);
            fclose($conn);
            fclose($socket);
            fwrite(STDERR, "Error: Authorization denied - {$params['error']}\n");
            return 1;
        }
        
        if (!isset($params['code'])) {
            $errorResponse = "HTTP/1.1 400 Bad Request\r\nContent-Type: text/html\r\n\r\n";
            $errorResponse .= "<h1>Error</h1><p>No authorization code received.</p>";
            fwrite($conn, $errorResponse);
            fclose($conn);
            fclose($socket);
            fwrite(STDERR, "Error: No authorization code in callback.\n");
            return 1;
        }
        
        $code = $params['code'];
        
        // Send success response to browser
        $successResponse = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n";
        $successResponse .= "<html><body style='font-family: system-ui; padding: 40px; text-align: center;'>";
        $successResponse .= "<h1>✓ Authorization Successful!</h1>";
        $successResponse .= "<p>You can close this window and return to the terminal.</p>";
        $successResponse .= "</body></html>";
        fwrite($conn, $successResponse);
        fclose($conn);
        fclose($socket);
        
        echo "\n✓ Received authorization code!\n";
        echo "Exchanging for tokens...\n";
        
        // Exchange code for tokens
        $tokenData = exchangeCodeForTokens($clientId, $clientSecret, $code);
        
        if (!$tokenData) {
            fwrite(STDERR, "Error: Failed to exchange code for tokens.\n");
            return 1;
        }
        
        // Save tokens
        file_put_contents(TOKEN_FILE, json_encode($tokenData, JSON_PRETTY_PRINT));
        chmod(TOKEN_FILE, 0600);
        
        echo "\n✓ Success! Tokens saved.\n";
        echo "  Refresh token: " . substr($tokenData['refresh_token'], 0, 20) . "...\n";
        
        return 0;
    }
    
    fclose($conn);
    fclose($socket);
    fwrite(STDERR, "Error: Invalid callback request.\n");
    return 1;
}

function exchangeCodeForTokens(string $clientId, string $clientSecret, string $code): ?array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://oauth2.googleapis.com/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => REDIRECT_URI,
            'grant_type' => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        fwrite(STDERR, "HTTP Error: {$httpCode}\n");
        fwrite(STDERR, "Response: {$response}\n");
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['access_token'])) {
        fwrite(STDERR, "Invalid token response: {$response}\n");
        return null;
    }
    
    return $data;
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

exit(main());
