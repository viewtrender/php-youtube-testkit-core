# YouTube Analytics API Reference

*Quick reference for YouTube Analytics API metrics and dimensions used in testkit fixtures*

**Source:** [Google Analytics API Docs](https://developers.google.com/youtube/analytics)

---

## Response Structure

```json
{
  "kind": "youtubeAnalytics#resultTable",
  "columnHeaders": [
    {"name": "day", "columnType": "DIMENSION", "dataType": "STRING"},
    {"name": "views", "columnType": "METRIC", "dataType": "INTEGER"}
  ],
  "rows": [
    ["2024-01-01", 1000],
    ["2024-01-02", 1200]
  ]
}
```

---

## Core Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `views` | INTEGER | Total video views |
| `estimatedMinutesWatched` | INTEGER | Total watch time in minutes |
| `averageViewDuration` | INTEGER | Average view duration in seconds |
| `averageViewPercentage` | FLOAT | Average percentage of video watched |
| `subscribersGained` | INTEGER | Subscribers gained |
| `subscribersLost` | INTEGER | Subscribers lost |
| `likes` | INTEGER | Likes |
| `dislikes` | INTEGER | Dislikes |
| `shares` | INTEGER | Shares |
| `comments` | INTEGER | Comments |

## Reach Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `videoThumbnailImpressions` | INTEGER | Thumbnail impressions |
| `videoThumbnailImpressionsClickRate` | FLOAT | Impressions CTR (0.0-1.0) |

## Revenue Metrics (Content Owner Only)

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `estimatedRevenue` | FLOAT | Estimated total revenue |
| `estimatedAdRevenue` | FLOAT | Ad revenue |
| `estimatedRedPartnerRevenue` | FLOAT | YouTube Premium revenue |
| `grossRevenue` | FLOAT | Gross ad revenue |
| `cpm` | FLOAT | CPM |
| `playbackBasedCpm` | FLOAT | Playback-based CPM |
| `monetizedPlaybacks` | INTEGER | Monetized playbacks |
| `adImpressions` | INTEGER | Ad impressions |

## End Screen & Card Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `endScreenElementClicks` | INTEGER | End screen clicks |
| `endScreenElementImpressions` | INTEGER | End screen impressions |
| `cardClicks` | INTEGER | Card clicks |
| `cardImpressions` | INTEGER | Card impressions |
| `cardClickRate` | FLOAT | Card click rate |
| `cardTeaserClicks` | INTEGER | Card teaser clicks |
| `cardTeaserImpressions` | INTEGER | Card teaser impressions |

## YouTube Premium Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `redViews` | INTEGER | YouTube Premium views |
| `estimatedRedMinutesWatched` | INTEGER | Premium watch time (minutes) |

## Playlist Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `playlistViews` | INTEGER | In-playlist views |
| `playlistEstimatedMinutesWatched` | INTEGER | Playlist watch time |
| `playlistStarts` | INTEGER | Playlist starts |
| `playlistAverageViewDuration` | INTEGER | Avg view duration in playlist |
| `averageTimeInPlaylist` | INTEGER | Avg time in playlist |
| `viewsPerPlaylistStart` | FLOAT | Views per playlist start |
| `playlistSaves` | INTEGER | Playlist saves |

## Live Stream Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `averageConcurrentViewers` | INTEGER | Average concurrent viewers |
| `peakConcurrentViewers` | INTEGER | Peak concurrent viewers |

---

## Dimensions

| Dimension Name | Data Type | Description | Example Values |
|----------------|-----------|-------------|----------------|
| `day` | STRING | Date (YYYY-MM-DD) | `2024-01-15` |
| `month` | STRING | Month (YYYY-MM) | `2024-01` |
| `video` | STRING | Video ID | `dQw4w9WgXcQ` |
| `channel` | STRING | Channel ID | `UCuAXFkgsw1L7xaCfnd5JJOw` |
| `country` | STRING | ISO country code | `US`, `GB`, `DE` |
| `province` | STRING | US state code | `US-CA`, `US-NY` |
| `ageGroup` | STRING | Age bracket | `age18-24`, `age25-34`, `age35-44`, `age45-54`, `age55-64`, `age65-` |
| `gender` | STRING | Gender | `male`, `female`, `user_specified` |
| `deviceType` | STRING | Device type | `MOBILE`, `DESKTOP`, `TABLET`, `TV`, `GAME_CONSOLE` |
| `operatingSystem` | STRING | OS | `ANDROID`, `IOS`, `WINDOWS`, `LINUX`, `MACINTOSH` |
| `insightTrafficSourceType` | STRING | Traffic source | See Traffic Sources below |
| `subscribedStatus` | STRING | Subscription status | `SUBSCRIBED`, `UNSUBSCRIBED` |
| `liveOrOnDemand` | STRING | Live vs VOD | `LIVE`, `ON_DEMAND` |
| `creatorContentType` | STRING | Content type | `VIDEO_ON_DEMAND`, `SHORTS`, `LIVE_STREAM`, `STORY` (Note: NOT "UPLOAD"/"SHORT") |

---

## Traffic Sources

| Traffic Source | Description |
|----------------|-------------|
| `ADVERTISING` | YouTube advertising |
| `ANNOTATION` | Video cards and annotations |
| `SUBSCRIBER` | Browse features |
| `YT_CHANNEL` | Channel pages |
| `NO_LINK_OTHER` | Direct or unknown |
| `END_SCREEN` | End screens |
| `EXT_URL` | External websites |
| `HASHTAGS` | Hashtag pages |
| `NOTIFICATION` | Notifications |
| `YT_OTHER_PAGE` | Other YouTube features |
| `PLAYLIST` | Playlists |
| `YT_PLAYLIST_PAGE` | Playlist page |
| `RELATED_VIDEO` / `YT_RELATED` | Suggested videos |
| `YT_SEARCH` | YouTube search |
| `SHORTS` | Shorts feed |

---

## Query Types Used in ViewTrender

### 1. Channel Overview
- **Metrics:** `views`, `estimatedMinutesWatched`, `averageViewDuration`, `subscribersGained`, `subscribersLost`, `likes`, `dislikes`, `shares`, `comments`
- **Dimensions:** (none - aggregate)

### 2. Daily Metrics
- **Metrics:** `views`, `estimatedMinutesWatched`, `subscribersGained`, `subscribersLost`
- **Dimensions:** `day`

### 3. Top Videos
- **Metrics:** `views`, `estimatedMinutesWatched`, `averageViewDuration`, `likes`, `comments`
- **Dimensions:** `video`
- **Sort:** `-views`

### 4. Traffic Sources
- **Dimensions:** `insightTrafficSourceType`, `creatorContentType`, `day`, `liveOrOnDemand`, `subscribedStatus`
- **Metrics:** `engagedViews`, `views`, `estimatedMinutesWatched`, `videoThumbnailImpressions`, `videoThumbnailImpressionsClickRate`

The traffic sources fixture contains the **maximal column set** (5 dimensions + 5 metrics).
`AnalyticsQueryResponse::trafficSources()` accepts optional `$dimensions` and `$metrics`
arrays to filter down to only the columns your query needs. `insightTrafficSourceType` is
always included as it is required by the API spec.

#### Usage examples

**Basic — all columns (no filtering):**

```php
$response = AnalyticsQueryResponse::trafficSources();
// Returns all 10 columns × 10 rows from the fixture
```

**Filtering to specific dimensions + metrics:**

```php
// Only traffic source type + content type, with views and impressions CTR
$response = AnalyticsQueryResponse::trafficSources(
    dimensions: ['creatorContentType'],
    metrics: ['views', 'videoThumbnailImpressionsClickRate'],
);
// columnHeaders: insightTrafficSourceType, creatorContentType, views, videoThumbnailImpressionsClickRate
// Row data is sliced to match — no manual alignment needed
```

**Mocking a filtered API response with overrides:**

```php
// Simulate a response with only SUBSCRIBED rows
$response = AnalyticsQueryResponse::trafficSources(
    dimensions: ['subscribedStatus'],
    metrics: ['views', 'estimatedMinutesWatched'],
    overrides: [
        'rows' => [
            ['YT_SEARCH',  'SUBSCRIBED', 150000, 75000],
            ['SUBSCRIBER', 'SUBSCRIBED', 200000, 100000],
        ],
    ],
);
```

**Building a response from scratch with `make()`:**

```php
$response = AnalyticsQueryResponse::make(
    columnHeaders: [
        ['name' => 'insightTrafficSourceType', 'columnType' => 'DIMENSION', 'dataType' => 'STRING'],
        ['name' => 'views',                    'columnType' => 'METRIC',    'dataType' => 'INTEGER'],
    ],
    rows: [
        ['YT_SEARCH', 42000],
        ['EXT_URL',   18000],
    ],
);
```

### 4b. Traffic Source Detail
- **Dimensions:** `insightTrafficSourceDetail`, `creatorContentType`
- **Metrics:** `engagedViews`, `views`, `estimatedMinutesWatched`, `videoThumbnailImpressions`, `videoThumbnailImpressionsClickRate`
- **Required filter:** `insightTrafficSourceType==SOURCE_TYPE` (e.g. `YT_SEARCH`, `RELATED_VIDEO`, `EXT_URL`)

The traffic source detail fixture contains the **maximal column set** (2 dimensions + 5 metrics).
`AnalyticsQueryResponse::trafficSourceDetail()` accepts optional `$dimensions` and `$metrics`
arrays to filter down to only the columns your query needs. `insightTrafficSourceDetail` is
always included as it is required by the API spec.

The fixture includes 10 rows spanning multiple traffic source types: search queries (rows 1-3),
related video IDs (rows 4-6), external URLs (rows 7-8), a playlist ID (row 9), and an
advertising format (row 10).

#### Usage examples

**Basic — all columns (no filtering):**

```php
$response = AnalyticsQueryResponse::trafficSourceDetail();
// Returns all 7 columns × 10 rows from the fixture
```

**Filtering to specific columns:**

```php
// Only detail + content type, with views and impressions CTR
$response = AnalyticsQueryResponse::trafficSourceDetail(
    dimensions: ['creatorContentType'],
    metrics: ['views', 'videoThumbnailImpressionsClickRate'],
);
// columnHeaders: insightTrafficSourceDetail, creatorContentType, views, videoThumbnailImpressionsClickRate
// Row data is sliced to match — no manual alignment needed
```

**Mocking a specific traffic source type with overrides:**

```php
// Simulate YT_SEARCH detail results with custom rows
$response = AnalyticsQueryResponse::trafficSourceDetail(
    metrics: ['views', 'estimatedMinutesWatched'],
    overrides: [
        'rows' => [
            ['how to edit videos', 'VIDEO_ON_DEMAND', 50000, 25000],
            ['best camera 2026',   'VIDEO_ON_DEMAND', 42000, 21000],
        ],
    ],
);
```

**Building a response from scratch with `make()`:**

```php
$response = AnalyticsQueryResponse::make(
    columnHeaders: [
        ['name' => 'insightTrafficSourceDetail', 'columnType' => 'DIMENSION', 'dataType' => 'STRING'],
        ['name' => 'views',                      'columnType' => 'METRIC',    'dataType' => 'INTEGER'],
    ],
    rows: [
        ['how to edit videos', 50000],
        ['best camera 2026',   42000],
    ],
);
```

### 5. Demographics
- **Metrics:** `viewerPercentage`
- **Dimensions:** `ageGroup`, `gender`

### 6. Geography
- **Metrics:** `views`, `estimatedMinutesWatched`
- **Dimensions:** `country`

### 7. Device Types
- **Metrics:** `views`, `estimatedMinutesWatched`
- **Dimensions:** `deviceType`

### 8. Video Analytics
- **Metrics:** `views`, `estimatedMinutesWatched`, `averageViewDuration`, `likes`, `comments`, `shares`, `subscribersGained`
- **Filters:** `video==VIDEO_ID`

### 9. Video Types
- **Metrics:** `views`
- **Dimensions:** `video`, `creatorContentType`

---

*Last updated: 2026-02-27*
