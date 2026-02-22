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
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Viewtrender\Youtube\YoutubeDataApi;
use Viewtrender\Youtube\Factories\YoutubeVideo;

class VideoServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }

    public function test_it_fetches_video_details(): void
    {
        YoutubeDataApi::fake([
            YoutubeVideo::listWithVideos([
                [
                    'id' => 'dQw4w9WgXcQ',
                    'snippet' => [
                        'title' => 'Never Gonna Give You Up',
                        'channelTitle' => 'Rick Astley',
                    ],
                    'statistics' => [
                        'viewCount' => '1500000000',
                        'likeCount' => '15000000',
                    ],
                ],
            ]),
        ]);

        $youtube = YoutubeDataApi::youtube();
        $response = $youtube->videos->listVideos('snippet,statistics', ['id' => 'dQw4w9WgXcQ']);

        $video = $response->getItems()[0];
        $this->assertSame('Never Gonna Give You Up', $video->getSnippet()->getTitle());
        $this->assertSame('1500000000', $video->getStatistics()->getViewCount());

        YoutubeDataApi::assertListedVideos();
        YoutubeDataApi::assertSentCount(1);
    }
}
```

### YouTube Analytics API

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Viewtrender\Youtube\YoutubeAnalyticsApi;
use Viewtrender\Youtube\Factories\AnalyticsQueryResponse;

class ChannelAnalyticsTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeAnalyticsApi::reset();
        parent::tearDown();
    }

    public function test_it_fetches_channel_overview_metrics(): void
    {
        YoutubeAnalyticsApi::fake([
            AnalyticsQueryResponse::channelOverview([
                'views' => 500000,
                'estimatedMinutesWatched' => 1500000,
                'subscribersGained' => 1000,
            ]),
        ]);

        $analytics = YoutubeAnalyticsApi::youtubeAnalytics();
        $response = $analytics->reports->query([
            'ids' => 'channel==MINE',
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-31',
            'metrics' => 'views,estimatedMinutesWatched,subscribersGained',
        ]);

        $row = $response->getRows()[0];
        $this->assertSame(500000, $row[0]);
        $this->assertSame(1500000, $row[1]);
        $this->assertSame(1000, $row[2]);

        YoutubeAnalyticsApi::assertSentCount(1);
    }
}
```

### YouTube Reporting API

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Viewtrender\Youtube\YoutubeReportingApi;
use Viewtrender\Youtube\Factories\ReportingJob;
use Viewtrender\Youtube\Factories\ReportingReport;
use Viewtrender\Youtube\Factories\ReportingMedia;

class ReportingPipelineTest extends TestCase
{
    protected function tearDown(): void
    {
        YoutubeReportingApi::reset();
        parent::tearDown();
    }

    public function test_it_lists_reporting_jobs(): void
    {
        YoutubeReportingApi::fake([
            ReportingJob::list([
                ['id' => 'job-1', 'reportTypeId' => 'channel_basic_a2', 'name' => 'Daily Stats'],
                ['id' => 'job-2', 'reportTypeId' => 'channel_demographics_a1', 'name' => 'Demographics'],
            ]),
        ]);

        $reporting = YoutubeReportingApi::youtubeReporting();
        $response = $reporting->jobs->listJobs();

        $this->assertCount(2, $response->getJobs());
        $this->assertSame('channel_basic_a2', $response->getJobs()[0]->getReportTypeId());

        YoutubeReportingApi::assertSentCount(1);
    }

    public function test_it_downloads_report_csv(): void
    {
        $csvContent = "date,channel_id,views,watch_time_minutes\n" .
                      "2024-01-01,UC123,1000,5000\n" .
                      "2024-01-02,UC123,1200,6000\n";

        YoutubeReportingApi::fake([
            ReportingMedia::download($csvContent),
        ]);

        $reporting = YoutubeReportingApi::youtubeReporting();
        $response = $reporting->media->download('resource-name');

        $this->assertStringContainsString('views,watch_time_minutes', $response->getBody()->getContents());

        YoutubeReportingApi::assertSentCount(1);
    }
}
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

### Pagination

Test code that paginates through multi-page API responses.

#### Auto-Generated Pages

Use `paginated()` to generate multiple pages of fake items:

```php
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;

// Generate 3 pages with 5 items each
YoutubeDataApi::fake([
    ...YoutubePlaylistItems::paginated(pages: 3, perPage: 5),
]);

// Test pagination logic
$youtube = YoutubeDataApi::youtube();
$pageToken = null;
$allItems = [];

do {
    $response = $youtube->playlistItems->listPlaylistItems(
        'snippet',
        ['playlistId' => 'PLxxx', 'pageToken' => $pageToken]
    );
    $allItems = array_merge($allItems, $response->getItems());
    $pageToken = $response->getNextPageToken();
} while ($pageToken !== null);

$this->assertCount(15, $allItems);
```

#### Explicit Page Contents

Use `pages()` for full control over each page's items:

```php
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;

YoutubeDataApi::fake([
    ...YoutubePlaylistItems::pages([
        // Page 1
        [
            ['snippet' => ['title' => 'First Video', 'resourceId' => ['videoId' => 'vid1']]],
            ['snippet' => ['title' => 'Second Video', 'resourceId' => ['videoId' => 'vid2']]],
        ],
        // Page 2 (last page)
        [
            ['snippet' => ['title' => 'Third Video', 'resourceId' => ['videoId' => 'vid3']]],
        ],
    ]),
]);
```

Pagination is available on `YoutubePlaylistItems` and `YoutubeActivities`.

See [docs/DATA_API.md](docs/DATA_API.md) for complete reference.

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

## License

MIT
