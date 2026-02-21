# php-youtube-testkit-core

Fake responses for YouTube Data API, Analytics API, and Reporting API in PHP tests.

[![Tests](https://github.com/viewtrender/php-youtube-testkit/actions/workflows/tests.yml/badge.svg)](https://github.com/viewtrender/php-youtube-testkit/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/viewtrender/php-youtube-testkit-core)](https://packagist.org/packages/viewtrender/php-youtube-testkit-core)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

**php-youtube-testkit-core** lets you test code that uses [`google/apiclient`](https://github.com/googleapis/google-api-php-client) without hitting real YouTube APIs. Queue fake responses, call Google services as normal, then assert which requests were made.

Supports three YouTube APIs:
- **YouTube Data API** — videos, channels, playlists, search, comments
- **YouTube Analytics API** — on-demand metrics queries
- **YouTube Reporting API** — bulk data exports and scheduled jobs

Framework-agnostic — works with any PHP project and any test runner.

## Installation

```bash
composer require --dev viewtrender/php-youtube-testkit-core
```

### Requirements

- PHP 8.3+
- `google/apiclient` ^2.15

## Quick Start

### YouTube Data API

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;

YoutubeDataApi::fake([
    YoutubeVideo::list(),
]);

$youtube = YoutubeDataApi::youtube();
$response = $youtube->videos->listVideos('snippet,statistics', ['id' => 'dQw4w9WgXcQ']);

YoutubeDataApi::assertListedVideos();
YoutubeDataApi::reset();
```

### YouTube Analytics API

```php
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;

YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::channelOverview([
        'views' => 500000,
        'estimatedMinutesWatched' => 1500000,
    ]),
]);

$analytics = YoutubeAnalyticsApi::youtubeAnalytics();
$response = $analytics->reports->query([
    'ids' => 'channel==MINE',
    'startDate' => '2024-01-01',
    'endDate' => '2024-01-31',
    'metrics' => 'views,estimatedMinutesWatched',
]);

YoutubeAnalyticsApi::assertSentCount(1);
YoutubeAnalyticsApi::reset();
```

### YouTube Reporting API

```php
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\ReportingJob;

YoutubeReportingApi::fake([
    ReportingJob::list([
        ['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2'],
    ]),
]);

$reporting = YoutubeReportingApi::youtubeReporting();
$response = $reporting->jobs->listJobs();

YoutubeReportingApi::assertSentCount(1);
YoutubeReportingApi::reset();
```

---

## YouTube Data API

### Setting up fakes

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\YoutubeChannel;

YoutubeDataApi::fake([
    YoutubeVideo::list(),      // first request
    YoutubeChannel::list(),    // second request
]);
```

### Data API Factories

| Factory | Methods |
|---------|---------|
| `YoutubeVideo` | `list()`, `listWithVideos()`, `video()`, `empty()`, `notFound()` |
| `YoutubeChannel` | `list()`, `listWithChannels()`, `channel()`, `empty()`, `notFound()` |
| `YoutubePlaylist` | `list()`, `listWithPlaylists()`, `playlist()`, `empty()` |
| `YoutubePlaylistItems` | `list()`, `listWithItems()`, `empty()` |
| `YoutubeSearchResult` | `list()`, `listWithResults()`, `searchResult()`, `empty()` |
| `YoutubeCommentThreads` | `list()`, `listWithThreads()`, `empty()` |
| `YoutubeComments` | `list()`, `listWithComments()`, `empty()` |
| `YoutubeSubscriptions` | `list()`, `listWithSubscriptions()`, `empty()` |
| `YoutubeActivities` | `list()`, `listWithActivities()`, `empty()` |
| `YoutubeCaptions` | `list()`, `listWithCaptions()`, `empty()` |

### Custom items

```php
YoutubeVideo::listWithVideos([
    [
        'id' => 'abc123',
        'snippet' => ['title' => 'My Custom Title'],
        'statistics' => ['viewCount' => '999'],
    ],
]);
```

### Data API Assertions

```php
YoutubeDataApi::assertListedVideos();
YoutubeDataApi::assertListedChannels();
YoutubeDataApi::assertListedPlaylists();
YoutubeDataApi::assertSearched();
YoutubeDataApi::assertSentCount(2);
YoutubeDataApi::assertNothingSent();
```

---

## YouTube Analytics API

For on-demand metrics queries — dashboards, real-time stats, custom date ranges.

### Setting up fakes

```php
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;

YoutubeAnalyticsApi::fake([
    AnalyticsQueryResponse::dailyMetrics([
        ['day' => '2024-01-01', 'views' => 1000],
        ['day' => '2024-01-02', 'views' => 1200],
    ]),
]);
```

### Analytics Factory Methods

| Method | Description |
|--------|-------------|
| `channelOverview()` | Channel-level aggregated metrics |
| `dailyMetrics()` | Day-by-day breakdown |
| `topVideos()` | Top performing videos |
| `trafficSources()` | Traffic source breakdown |
| `demographics()` | Age/gender viewer percentages |
| `geography()` | Country-level metrics |
| `deviceTypes()` | Device type breakdown |
| `videoAnalytics()` | Per-video metrics |
| `videoTypes()` | Metrics by content type |

### Channel Overview

```php
AnalyticsQueryResponse::channelOverview([
    'views' => 500000,
    'estimatedMinutesWatched' => 1500000,
    'averageViewDuration' => 180,
    'subscribersGained' => 1000,
    'subscribersLost' => 50,
]);
```

### Traffic Sources

```php
AnalyticsQueryResponse::trafficSources([
    ['source' => 'RELATED_VIDEO', 'views' => 500000],
    ['source' => 'YT_SEARCH', 'views' => 300000],
    ['source' => 'EXT_URL', 'views' => 100000],
]);
```

### Demographics

```php
AnalyticsQueryResponse::demographics([
    ['ageGroup' => 'age18-24', 'gender' => 'male', 'viewerPercentage' => 25.5],
    ['ageGroup' => 'age25-34', 'gender' => 'female', 'viewerPercentage' => 18.2],
]);
```

### Content Types

```php
AnalyticsQueryResponse::videoTypes([
    ['video' => 'abc123', 'creatorContentType' => 'VIDEO_ON_DEMAND', 'views' => 180000],
    ['video' => 'def456', 'creatorContentType' => 'SHORTS', 'views' => 120000],
    ['video' => 'ghi789', 'creatorContentType' => 'LIVE_STREAM', 'views' => 50000],
]);
```

See [docs/ANALYTICS_API.md](docs/ANALYTICS_API.md) for complete metrics and dimensions reference.

---

## YouTube Reporting API

For bulk data exports — background jobs, historical data pipelines, scheduled reports.

**Workflow:** Create job → Poll for reports → Download CSV → Parse & upsert

### Setting up fakes

```php
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\Factories\ReportingReportType;
use Viewtrender\Youtube\Factories\ReportingMedia;

YoutubeReportingApi::fake([
    ReportingJob::create(['id' => 'job-123', 'reportTypeId' => 'channel_basic_a2']),
]);
```

### Reporting Factories

| Factory | Methods |
|---------|---------|
| `ReportingJob` | `create()`, `list()`, `delete()` |
| `ReportingReport` | `list()`, `get()` |
| `ReportingReportType` | `list()` |
| `ReportingMedia` | `download()` |

### Creating a Job

```php
ReportingJob::create([
    'id' => 'job-123',
    'reportTypeId' => 'channel_basic_a2',
    'name' => 'My Channel Report',
]);
```

### Listing Jobs

```php
ReportingJob::list([
    ['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2', 'name' => 'Job 1'],
    ['id' => 'job-2', 'reportTypeId' => 'channel_demographics_a1', 'name' => 'Job 2'],
]);
```

### Listing Reports

```php
ReportingReport::list([
    [
        'id' => 'report-1',
        'jobId' => 'job-123',
        'startTime' => '2024-01-01T00:00:00Z',
        'endTime' => '2024-01-02T00:00:00Z',
        'downloadUrl' => 'https://youtube.com/reporting/v1/media/report-1',
    ],
]);
```

### Downloading CSV Data

```php
$csv = "date,channel_id,views,watch_time_minutes\n" .
       "2024-01-01,UC123,1000,5000\n";

ReportingMedia::download($csv);
```

### Common Report Types

| Report Type ID | Description |
|----------------|-------------|
| `channel_basic_a2` | Core channel metrics |
| `channel_demographics_a1` | Viewer age/gender |
| `channel_device_os_a2` | Device and OS distribution |
| `channel_traffic_source_a2` | Traffic source breakdown |

See [docs/REPORTING_API.md](docs/REPORTING_API.md) for complete reference.

---

## Error Responses

Simulate API errors for any service:

```php
use Viewtrender\Youtube\Responses\ErrorResponse;

YoutubeDataApi::fake([
    ErrorResponse::notFound('Video not found.'),
    ErrorResponse::quotaExceeded('Daily quota exhausted.'),
    ErrorResponse::forbidden(),
    ErrorResponse::unauthorized(),
    ErrorResponse::badRequest(),
]);
```

---

## Custom Responses

Build arbitrary responses with `FakeResponse`:

```php
use Viewtrender\Youtube\Responses\FakeResponse;

FakeResponse::make(['kind' => 'youtube#videoListResponse', 'items' => []]);
FakeResponse::fromFixture('youtube/videos-list.json');
FakeResponse::make(['error' => 'bad'])->status(400);
```

---

## Preventing Stray Requests

Throw an exception when no fake response is queued:

```php
$fake = YoutubeDataApi::fake([YoutubeVideo::list()]);
$fake->preventStrayRequests();
```

---

## Framework Integration

Register a callback that runs whenever `fake()` is called:

```php
YoutubeDataApi::registerContainerSwap(function () {
    $container->set(YouTube::class, new YouTube(YoutubeDataApi::client()));
});
```

---

## Full Test Example

```php
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;
use Viewtrender\Youtube\Factories\ReportingJob;

class MyYoutubeTest extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        YoutubeAnalyticsApi::reset();
        YoutubeReportingApi::reset();
        parent::tearDown();
    }

    public function test_fetches_video_details(): void
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
    }

    public function test_fetches_channel_analytics(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview(['views' => 500000]),
        ]);

        $analytics = YoutubeAnalyticsApi::youtubeAnalytics();
        $response = $analytics->reports->query([
            'ids' => 'channel==MINE',
            'metrics' => 'views',
        ]);

        $this->assertSame(500000, $response->getRows()[0][0]);
    }

    public function test_lists_reporting_jobs(): void
    {
        YoutubeReportingApi::fake([
            ReportingJob::list([
                ['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2'],
            ]),
        ]);

        $reporting = YoutubeReportingApi::youtubeReporting();
        $jobs = $reporting->jobs->listJobs();

        $this->assertCount(1, $jobs->getJobs());
    }
}
```

## License

MIT
