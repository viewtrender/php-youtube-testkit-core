# YouTube Data API Reference

*Quick reference for YouTube Data API factories and pagination support*

**Source:** [Google YouTube Data API Docs](https://developers.google.com/youtube/v3)

---

## Factory Overview

All YouTube Data API factories follow a consistent pattern with these methods:

| Method | Description |
|--------|-------------|
| `list()` | Full list response from fixture with optional top-level overrides |
| `listWith{Resource}s()` | List response with custom items (deep-merged from fixture) |
| `{resource}()` | Single item array with optional overrides |
| `empty()` | Valid list response with zero items |

---

## Available Factories

| Factory | Item Type | Fixture |
|---------|-----------|---------|
| `YoutubeVideo` | video | videos-list.json |
| `YoutubeChannel` | channel | channels-list.json |
| `YoutubePlaylist` | playlist | playlists-list.json |
| `YoutubePlaylistItems` | playlistItem | playlist-items-list.json |
| `YoutubeSearchResult` | searchResult | search-list.json |
| `YoutubeCommentThreads` | commentThread | comment-threads-list.json |
| `YoutubeComments` | comment | comments-list.json |
| `YoutubeSubscriptions` | subscription | subscriptions-list.json |
| `YoutubeActivities` | activity | activities-list.json |
| `YoutubeCaptions` | caption | captions-list.json |
| `YoutubeChannelSections` | channelSection | channel-sections-list.json |
| `YoutubeGuideCategories` | guideCategory | guide-categories-list.json |
| `YoutubeI18nLanguages` | language | i18n-languages-list.json |
| `YoutubeI18nRegions` | region | i18n-regions-list.json |
| `YoutubeMembers` | member | members-list.json |
| `YoutubeMembershipsLevels` | membershipsLevel | memberships-levels-list.json |
| `YoutubeVideoAbuseReportReasons` | reason | video-abuse-report-reasons-list.json |
| `YoutubeVideoCategories` | videoCategory | video-categories-list.json |
| `YoutubeThumbnails` | thumbnail | thumbnails-set.json |
| `YoutubeWatermarks` | watermark | watermarks-set.json |

---

## Basic Usage

### List Response with Defaults

```php
use Viewtrender\Youtube\Factories\YoutubeVideo;

// Full response from fixture
$response = YoutubeVideo::list();

// With top-level overrides
$response = YoutubeVideo::list([
    'nextPageToken' => 'CUSTOM_TOKEN',
]);
```

### List Response with Custom Items

```php
use Viewtrender\Youtube\Factories\YoutubeVideo;

$response = YoutubeVideo::listWithVideos([
    [
        'id' => 'abc123',
        'snippet' => ['title' => 'My Video Title'],
        'statistics' => ['viewCount' => '1000000'],
    ],
    [
        'id' => 'def456',
        'snippet' => ['title' => 'Another Video'],
        'statistics' => ['viewCount' => '500000'],
    ],
]);
```

Items are deep-merged with the fixture's first item as base, so you only need to specify the fields you want to override.

### Single Item

```php
use Viewtrender\Youtube\Factories\YoutubeVideo;

$video = YoutubeVideo::video([
    'id' => 'custom-id',
    'snippet' => ['title' => 'Custom Title'],
]);
```

### Empty Response

```php
use Viewtrender\Youtube\Factories\YoutubeVideo;

$response = YoutubeVideo::empty();
// Returns valid response with items: [], totalResults: 0
```

---

## Pagination

Factories for paginated endpoints (`YoutubePlaylistItems`, `YoutubeActivities`) support pagination testing.

### Auto-Generated Pages

Use `paginated()` to generate multiple pages of fake items:

```php
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;
use Viewtrender\Youtube\YoutubeDataApi;

// Generate 3 pages with 5 items each
YoutubeDataApi::fake([
    ...YoutubePlaylistItems::paginated(pages: 3, perPage: 5),
]);

// First two responses have nextPageToken, last does not
// Tokens are: 'page_token_2', 'page_token_3', etc.
```

### Explicit Page Contents

Use `pages()` to provide exact items for each page:

```php
use Viewtrender\Youtube\Factories\YoutubePlaylistItems;
use Viewtrender\Youtube\YoutubeDataApi;

YoutubeDataApi::fake([
    ...YoutubePlaylistItems::pages([
        // Page 1 items
        [
            ['snippet' => ['title' => 'Video 1', 'resourceId' => ['videoId' => 'vid1']]],
            ['snippet' => ['title' => 'Video 2', 'resourceId' => ['videoId' => 'vid2']]],
        ],
        // Page 2 items
        [
            ['snippet' => ['title' => 'Video 3', 'resourceId' => ['videoId' => 'vid3']]],
        ],
    ]),
]);
```

### Pagination Token Rules

| Scenario | nextPageToken |
|----------|---------------|
| Intermediate pages | `page_token_2`, `page_token_3`, etc. |
| Last page | Not present |
| Single page (`pages: 1`) | Not present |
| `empty()` | Not present |
| `listWith{Resource}s()` | Not present |

---

## Response Structure

All list responses share this structure:

```json
{
  "kind": "youtube#{resource}ListResponse",
  "etag": "...",
  "pageInfo": {
    "totalResults": 1,
    "resultsPerPage": 1
  },
  "items": [
    {
      "kind": "youtube#{resource}",
      "etag": "...",
      "id": "...",
      "snippet": { ... },
      ...
    }
  ]
}
```

Paginated endpoints may also include:

```json
{
  "nextPageToken": "CAUQAA",
  "prevPageToken": "BBEQAA"
}
```

---

## Factory-Specific Notes

### YoutubeVideo

Common parts: `snippet`, `contentDetails`, `statistics`, `status`, `player`, `topicDetails`, `recordingDetails`, `liveStreamingDetails`

```php
YoutubeVideo::listWithVideos([
    [
        'id' => 'video-id',
        'snippet' => [
            'title' => 'Video Title',
            'description' => 'Description',
            'channelId' => 'channel-id',
            'channelTitle' => 'Channel Name',
            'publishedAt' => '2024-01-15T12:00:00Z',
        ],
        'statistics' => [
            'viewCount' => '1000000',
            'likeCount' => '50000',
            'commentCount' => '5000',
        ],
    ],
]);
```

### YoutubeChannel

Common parts: `snippet`, `contentDetails`, `statistics`, `brandingSettings`, `topicDetails`

```php
YoutubeChannel::listWithChannels([
    [
        'id' => 'channel-id',
        'snippet' => [
            'title' => 'Channel Name',
            'description' => 'Channel Description',
            'customUrl' => '@channelhandle',
        ],
        'statistics' => [
            'subscriberCount' => '1000000',
            'videoCount' => '500',
            'viewCount' => '100000000',
        ],
    ],
]);
```

### YoutubePlaylistItems

Common parts: `snippet`, `contentDetails`, `status`

```php
YoutubePlaylistItems::listWithPlaylistItems([
    [
        'snippet' => [
            'title' => 'Video in Playlist',
            'position' => 0,
            'playlistId' => 'playlist-id',
            'resourceId' => [
                'kind' => 'youtube#video',
                'videoId' => 'video-id',
            ],
        ],
    ],
]);
```

### YoutubeSearchResult

Common parts: `snippet`, `id`

```php
YoutubeSearchResult::listWithResults([
    [
        'id' => [
            'kind' => 'youtube#video',
            'videoId' => 'search-result-id',
        ],
        'snippet' => [
            'title' => 'Search Result Title',
            'channelTitle' => 'Channel Name',
        ],
    ],
]);
```

---

*Last updated: 2026-02-21*
