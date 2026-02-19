# php-youtube-testkit-core

Fake responses for the Google YouTube Data API v3 in PHP tests.

[![Tests](https://github.com/viewtrender/php-youtube-testkit/actions/workflows/tests.yml/badge.svg)](https://github.com/viewtrender/php-youtube-testkit/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/viewtrender/php-youtube-testkit-core)](https://packagist.org/packages/viewtrender/php-youtube-testkit-core)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

**php-youtube-testkit-core** lets you test code that uses [`google/apiclient`](https://github.com/googleapis/google-api-php-client) without hitting real YouTube APIs. Queue fake responses, call
`Google\Service\YouTube` as normal, then assert which requests were made.

Framework-agnostic — works with any PHP project and any test runner.

## Installation

```bash
composer require --dev viewtrender/php-youtube-testkit-core
```

### Requirements

- PHP 8.3+
- `google/apiclient` ^2.15

## Quick Start

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;

// 1. Activate fakes and queue a response
YoutubeDataApi::fake([
    YoutubeVideo::list(),
]);

// 2. Use the YouTube service as normal
$youtube = YoutubeDataApi::youtube();
$response = $youtube->videos->listVideos('snippet,statistics', ['id' => 'dQw4w9WgXcQ']);

// 3. Assert the request was made
YoutubeDataApi::assertListedVideos();
YoutubeDataApi::assertSentCount(1);

// 4. Clean up
YoutubeDataApi::reset();
```

## Usage

### Setting up fakes

Call `YoutubeDataApi::fake()` with an array of responses. Each response is consumed in order as your code makes requests:

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;

YoutubeDataApi::fake([
    YoutubeVideo::list(),      // first request gets this
    YoutubeChannel::list(),    // second request gets this
]);
```

### Getting a YouTube service

Get a pre-configured `Google\Service\YouTube` instance directly:

```php
$youtube = YoutubeDataApi::youtube();
```

Or create one from the fake Google Client:

```php
use Google\Service\YouTube;

$youtube = new YouTube(YoutubeDataApi::client());
```

### Resetting state

Always reset the fake state after each test:

```php
protected function tearDown(): void
{
    YoutubeDataApi::reset();
    parent::tearDown();
}
```

## Factories

Four factories are available, each backed by realistic fixture data:

| Factory               | List method | List with items       | Single item      | Empty     |
|-----------------------|-------------|-----------------------|------------------|-----------|
| `YoutubeVideo`        | `list()`    | `listWithVideos()`    | `video()`        | `empty()` |
| `YoutubeChannel`      | `list()`    | `listWithChannels()`  | `channel()`      | `empty()` |
| `YoutubePlaylist`     | `list()`    | `listWithPlaylists()` | `playlist()`     | `empty()` |
| `YoutubeSearchResult` | `list()`    | `listWithResults()`   | `searchResult()` | `empty()` |

### Default response

Returns the full fixture with realistic defaults:

```php
YoutubeVideo::list();
```

### Custom items

Pass an array of items — only specify the fields you care about. Unspecified fields use fixture defaults via deep merge:

```php
YoutubeVideo::listWithVideos([
    [
        'id' => 'abc123',
        'snippet' => ['title' => 'My Custom Title'],
        'statistics' => ['viewCount' => '999'],
    ],
    [
        'id' => 'def456',
        'snippet' => ['title' => 'Another Video'],
    ],
]);
```

### Single item builder

Build a single item array (useful for composing custom responses):

```php
$video = YoutubeVideo::video(['id' => 'abc123']);
```

### Empty response

Returns a valid API response with zero items:

```php
YoutubeVideo::empty();
```

## Error Responses

Simulate YouTube API errors:

```php
use Viewtrender\Youtube\Responses\ErrorResponse;

YoutubeDataApi::fake([
    ErrorResponse::notFound(),
    ErrorResponse::forbidden(),
    ErrorResponse::unauthorized(),
    ErrorResponse::quotaExceeded(),
    ErrorResponse::badRequest(),
]);
```

Each method accepts an optional custom message:

```php
ErrorResponse::notFound('Video not found.');
ErrorResponse::quotaExceeded('Daily quota exhausted.');
```

## Custom Responses

Build arbitrary responses with `FakeResponse`:

```php
use Viewtrender\Youtube\Responses\FakeResponse;

// From an array
FakeResponse::make(['kind' => 'youtube#videoListResponse', 'items' => []]);

// From a raw JSON string
FakeResponse::make('{"kind":"youtube#videoListResponse","items":[]}');

// From a fixture file (relative to src/Fixtures/)
FakeResponse::fromFixture('youtube/videos-list.json');

// With status code and headers
FakeResponse::make(['error' => 'bad'])->status(400)->header('X-Custom', 'value');
```

## Assertions

### Request assertions

```php
use Psr\Http\Message\RequestInterface;

// At least one request matched the callback
YoutubeDataApi::assertSent(function (RequestInterface $request): bool {
    return str_contains($request->getUri()->getPath(), '/youtube/v3/videos')
        && str_contains((string) $request->getUri(), 'dQw4w9WgXcQ');
});

// No request matched the callback
YoutubeDataApi::assertNotSent(function (RequestInterface $request): bool {
    return str_contains($request->getUri()->getPath(), '/youtube/v3/channels');
});

// No requests were sent at all
YoutubeDataApi::assertNothingSent();

// Exact number of requests
YoutubeDataApi::assertSentCount(2);
```

### Path shorthand assertions

```php
YoutubeDataApi::assertListedVideos();      // /youtube/v3/videos
YoutubeDataApi::assertSearched();          // /youtube/v3/search
YoutubeDataApi::assertListedChannels();    // /youtube/v3/channels
YoutubeDataApi::assertListedPlaylists();   // /youtube/v3/playlists
YoutubeDataApi::assertCalledPath('/youtube/v3/custom');
```

## Preventing Stray Requests

Throw an exception when a request is made but no fake response is queued:

```php
$fake = YoutubeDataApi::fake([
    YoutubeVideo::list(),
]);

$fake->preventStrayRequests();
```

Any unmatched request throws `Viewtrender\Youtube\Exceptions\StrayRequestException`.

## Full Test Example

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;
use Viewtrender\Youtube\Responses\ErrorResponse;
use Psr\Http\Message\RequestInterface;

class MyYoutubeTest extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_it_fetches_videos(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::listWithVideos([
                ['id' => 'abc123', 'snippet' => ['title' => 'Test Video']],
            ]),
        ]);

        $youtube = YoutubeDataApi::youtube();
        $response = $youtube->videos->listVideos('snippet', ['id' => 'abc123']);

        $this->assertSame('Test Video', $response->getItems()[0]->getSnippet()->getTitle());
        YoutubeDataApi::assertListedVideos();
        YoutubeDataApi::assertSentCount(1);
    }

    public function test_it_handles_quota_exceeded(): void
    {
        YoutubeDataApi::fake([
            ErrorResponse::quotaExceeded(),
        ]);

        $youtube = YoutubeDataApi::youtube();

        $this->expectException(\Google\Service\Exception::class);
        $youtube->videos->listVideos('snippet', ['id' => 'any']);
    }
}
```

## Framework Integration

The core package provides a `registerContainerSwap()` hook for framework integrations. Register a callback that runs whenever `YoutubeDataApi::fake()` is called — useful for rebinding services in a DI
container:

```php
YoutubeDataApi::registerContainerSwap(function () {
    // Rebind YouTube service in your container with the fake client
    $container->set(YouTube::class, new YouTube(YoutubeDataApi::client()));
});
```

## License

MIT
