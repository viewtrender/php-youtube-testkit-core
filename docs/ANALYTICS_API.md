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
- **Metrics:** `views`, `estimatedMinutesWatched`
- **Dimensions:** `insightTrafficSourceType`

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

*Last updated: 2026-02-21*
