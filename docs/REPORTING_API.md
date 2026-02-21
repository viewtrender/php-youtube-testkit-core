# YouTube Reporting API Reference

*Quick reference for YouTube Reporting API metrics, dimensions, and report types*

**Source:** [Google Reporting API Docs](https://developers.google.com/youtube/reporting/v1/reports)

---

## Overview

The Reporting API provides bulk data exports via scheduled jobs. Unlike the Analytics API (on-demand queries), Reporting API:

- Generates reports daily (24-72 hour delay)
- Provides CSV downloads
- Uses lowercase_underscore naming convention
- Supports historical data

---

## Job Response Structure

```json
{
  "jobs": [
    {
      "id": "job_abc123",
      "reportTypeId": "channel_basic_a2",
      "name": "My Channel Report",
      "createTime": "2024-01-01T00:00:00Z",
      "expireTime": "2025-01-01T00:00:00Z",
      "systemManaged": false
    }
  ]
}
```

## Report Response Structure

```json
{
  "reports": [
    {
      "id": "report_xyz789",
      "jobId": "job_abc123",
      "startTime": "2024-01-01T00:00:00Z",
      "endTime": "2024-01-02T00:00:00Z",
      "createTime": "2024-01-03T00:00:00Z",
      "downloadUrl": "https://youtubereporting.googleapis.com/..."
    }
  ]
}
```

---

## Report Types

### Channel Reports

| Report Type ID | Description |
|----------------|-------------|
| `channel_basic_a2` | Basic channel stats (views, watch time, subs) |
| `channel_demographics_a1` | Age and gender breakdown |
| `channel_device_os_a2` | Device and OS breakdown |
| `channel_traffic_source_a2` | Traffic source breakdown |
| `channel_playback_location_a2` | Playback location breakdown |
| `channel_subtitles_a2` | Subtitle/CC usage |
| `channel_combined_a2` | Combined report with all dimensions |
| `channel_end_screens_a1` | End screen performance |
| `channel_cards_a1` | Card performance |
| `channel_annotations_a1` | Annotation performance |
| `channel_reach_combined_a1` | Impressions and CTR by traffic source |

### Content Owner Reports

| Report Type ID | Description |
|----------------|-------------|
| `content_owner_basic_a3` | Basic stats across all channels |
| `content_owner_demographics_a1` | Demographics across channels |
| `content_owner_ad_rates_a1` | Ad performance and revenue |
| `content_owner_estimated_revenue_a1` | Revenue breakdown |
| `content_owner_asset_estimated_revenue_a1` | Asset-level revenue |
| `content_owner_claimed_videos_a2` | Claimed video stats |

---

## Core Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `views` | INTEGER | Total views |
| `watch_time_minutes` | INTEGER | Watch time in minutes |
| `average_view_duration_seconds` | INTEGER | Average view duration |
| `average_view_duration_percentage` | FLOAT | Average % watched |
| `subscribers_gained` | INTEGER | Subscribers gained |
| `subscribers_lost` | INTEGER | Subscribers lost |
| `likes` | INTEGER | Likes |
| `dislikes` | INTEGER | Dislikes |
| `shares` | INTEGER | Shares |
| `comments` | INTEGER | Comments |

## Reach Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `video_thumbnail_impressions` | INTEGER | Thumbnail impressions |
| `video_thumbnail_impressions_ctr` | FLOAT | Impressions CTR |

## Revenue Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `estimated_partner_revenue` | FLOAT | Total estimated revenue |
| `estimated_partner_ad_revenue` | FLOAT | Ad revenue |
| `estimated_partner_red_revenue` | FLOAT | YouTube Premium revenue |
| `estimated_partner_transaction_revenue` | FLOAT | Transaction revenue |
| `estimated_youtube_ad_revenue` | FLOAT | YouTube's ad revenue share |
| `estimated_partner_ad_auction_revenue` | FLOAT | AdSense revenue |
| `ad_impressions` | INTEGER | Ad impressions |
| `estimated_cpm` | FLOAT | CPM |
| `estimated_playback_based_cpm` | FLOAT | Playback-based CPM |
| `estimated_monetized_playbacks` | INTEGER | Monetized playbacks |

## YouTube Premium Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `red_views` | INTEGER | Premium views |
| `red_watch_time_minutes` | INTEGER | Premium watch time |

## End Screen & Card Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `end_screen_element_clicks` | INTEGER | End screen clicks |
| `end_screen_element_impressions` | INTEGER | End screen impressions |
| `end_screen_element_click_rate` | FLOAT | End screen CTR |
| `card_clicks` | INTEGER | Card clicks |
| `card_impressions` | INTEGER | Card impressions |
| `card_click_rate` | FLOAT | Card CTR |
| `card_teaser_clicks` | INTEGER | Card teaser clicks |
| `card_teaser_impressions` | INTEGER | Card teaser impressions |
| `card_teaser_click_rate` | FLOAT | Card teaser CTR |

## Playlist Metrics

| Metric Name | Data Type | Description |
|-------------|-----------|-------------|
| `playlist_views` | INTEGER | Playlist views |
| `playlist_starts` | INTEGER | Playlist starts |

---

## Dimensions

| Dimension Name | Data Type | Description | Example Values |
|----------------|-----------|-------------|----------------|
| `date` | STRING | Date (YYYY-MM-DD) | `2024-01-15` |
| `video_id` | STRING | Video ID | `dQw4w9WgXcQ` |
| `channel_id` | STRING | Channel ID | `UCuAXFkgsw1L7xaCfnd5JJOw` |
| `country_code` | STRING | ISO country code | `US`, `GB`, `DE` |
| `province_code` | STRING | US state code | `US-CA`, `US-NY` |
| `age_group` | STRING | Age bracket | `AGE_18_24`, `AGE_25_34` |
| `gender` | STRING | Gender | `MALE`, `FEMALE` |
| `device_type` | STRING | Device type | `MOBILE`, `DESKTOP`, `TABLET`, `TV` |
| `operating_system` | STRING | OS | `ANDROID`, `IOS`, `WINDOWS` |
| `traffic_source_type` | INTEGER | Traffic source code | See Traffic Sources below |
| `traffic_source_detail` | STRING | Traffic source detail | Search terms, channel IDs |
| `subscribed_status` | STRING | Subscription status | `SUBSCRIBED`, `UNSUBSCRIBED` |
| `live_or_on_demand` | STRING | Live vs VOD | `LIVE`, `ON_DEMAND` |
| `playback_location_type` | INTEGER | Playback location | `0`=watch page, `1`=embedded |

---

## Traffic Source Codes

| Code | Traffic Source |
|------|----------------|
| `0` | Direct or unknown |
| `1` | YouTube advertising |
| `3` | Browse features |
| `4` | Channel pages |
| `5` | YouTube search |
| `7` | Suggested videos |
| `8` | Other YouTube features |
| `9` | External |
| `11` | Video cards and annotations |
| `14` | Playlists |
| `17` | Notifications |
| `18` | Playlist page |
| `19` | Campaign cards |
| `20` | End screens |
| `23` | Stories (deprecated) |
| `24` | Shorts feed |
| `25` | Product page |
| `26` | Hashtag pages |
| `27` | Sound page |
| `28` | Live redirect |
| `29` | Podcasts |
| `30` | Remixed video |
| `31` | Vertical live feed |
| `32` | Related video (Shorts) |

---

## CSV Report Format

Reports are downloaded as CSV with header row:

```csv
date,channel_id,video_id,views,watch_time_minutes,subscribers_gained
2024-01-15,UCtest123,vid123,1500,750,25
2024-01-15,UCtest123,vid456,2000,1200,40
2024-01-16,UCtest123,vid123,1600,800,30
```

**Column naming:** All columns use `lowercase_underscore` format.

---

## Workflow

1. **List report types:** `GET /v1/reportTypes`
2. **Create job:** `POST /v1/jobs` with `reportTypeId` and `name`
3. **Poll for reports:** `GET /v1/jobs/{jobId}/reports`
4. **Download report:** `GET {downloadUrl}` → CSV data

---

*Last updated: 2026-02-21*
