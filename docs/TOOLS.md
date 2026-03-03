# CLI Tools

Tools for querying the YouTube APIs and capturing fixture data.

All tools load credentials from `.env` in the project root (see [Setup](#setup)).

---

## Setup

### 1. Create `.env` from the example

```bash
cp .env.example .env
```

### 2. Fill in your credentials

```dotenv
# Public data queries (videos, channels, etc.)
YOUTUBE_API_KEY=your-api-key

# OAuth queries (analytics, subscriptions, etc.)
YOUTUBE_CLIENT_ID=your-client-id.apps.googleusercontent.com
YOUTUBE_CLIENT_SECRET=GOCSPX-your-secret
```

**Where to get these:**
- **API Key:** [Google Cloud Console → APIs & Services → Credentials](https://console.cloud.google.com/apis/credentials) → Create Credentials → API key
- **OAuth Client:** Same page → Create Credentials → OAuth client ID → Application type: Web application

### 3. Configure OAuth redirect URI

In the Google Cloud Console, add this to your OAuth client's **Authorized redirect URIs**:

```
http://localhost:8765/callback
```

### 4. Authorize a YouTube channel

```bash
php tools/youtube-oauth-setup.php
```

This will:
1. Start a local callback server on port 8765
2. Print an authorization URL — open it in your browser
3. Sign in with the Google account that owns the YouTube channel
4. Grant access to the requested scopes (`youtube.readonly`, `yt-analytics.readonly`)
5. Save the refresh token to `tools/.youtube-oauth-token`

The token persists across sessions. The tool auto-refreshes expired access tokens.

**To re-authorize** (different channel or expired refresh token): just run the setup again.

### 5. Verify it works

```bash
php tools/youtube-analytics-query.php --preset=channel-overview
```

If using a specific channel (not `channel==MINE`):

```bash
php tools/youtube-analytics-query.php --preset=channel-overview --ids="channel==UCxxxxxxxxx"
```

---

## Security

The following files contain secrets and are **gitignored** — never commit them:

| File | Contains |
|------|----------|
| `.env` | API key, OAuth client ID/secret |
| `tools/.youtube-oauth-token` | OAuth refresh + access tokens |

The `.env.example` file is safe to commit (no real values).

---

## youtube-analytics-query.php

Query the YouTube Analytics API (v2) using the Google PHP SDK. Returns raw JSON matching the `youtubeAnalytics#resultTable` format used by test fixtures.

### Quick start with presets

```bash
# List all available presets
php tools/youtube-analytics-query.php --list-presets

# Use a preset
php tools/youtube-analytics-query.php --preset=playback-locations

# Save directly as a fixture
php tools/youtube-analytics-query.php --preset=playback-locations --save=playback-locations
```

### Custom queries

```bash
php tools/youtube-analytics-query.php \
    --dimensions=operatingSystem \
    --metrics=views,estimatedMinutesWatched \
    --sort=-views \
    --startDate=2025-01-01 \
    --endDate=2025-12-31
```

### All options

| Option | Description | Default |
|--------|-------------|---------|
| `--preset=<name>` | Use a predefined report config | — |
| `--ids=<value>` | Channel identifier | `channel==MINE` |
| `--dimensions=<dims>` | Comma-separated dimensions | — |
| `--metrics=<metrics>` | Comma-separated metrics (required unless preset) | — |
| `--filters=<filters>` | Semicolon-separated filters | — |
| `--sort=<sort>` | Sort order (`-` prefix = descending) | — |
| `--maxResults=<n>` | Max rows | — |
| `--startDate=<date>` | YYYY-MM-DD | 90 days ago |
| `--endDate=<date>` | YYYY-MM-DD | yesterday |
| `--save=<name>` | Save to `src/Fixtures/analytics/<name>.json` | — |
| `--raw` | Output without pretty printing | — |

### Available presets

| Preset | Dimensions | Notes |
|--------|-----------|-------|
| `channel-overview` | (none) | Aggregate channel stats |
| `daily-metrics` | `day` | |
| `top-videos` | `video` | Top 10 by views |
| `traffic-sources` | `insightTrafficSourceType` | |
| `traffic-source-detail` | `insightTrafficSourceDetail` | Needs traffic source type filter |
| `playback-locations` | `insightPlaybackLocationType` | |
| `playback-location-detail` | `insightPlaybackLocationDetail` | Needs video + location type filter |
| `operating-systems` | `operatingSystem` | |
| `device-types` | `deviceType` | |
| `device-os-combo` | `deviceType,operatingSystem` | |
| `demographics` | `ageGroup,gender` | |
| `geography` | `country` | |
| `sharing-service` | `sharingService` | |
| `audience-retention` | `elapsedVideoTimeRatio` | Requires `video==ID` filter |
| `video-types` | `creatorContentType` | |

---

## youtube-raw-query.php

Query the YouTube Data API (v3) for public resource data. Uses API key by default, OAuth with `--auth`.

```bash
# Public data
php tools/youtube-raw-query.php videos --id=dQw4w9WgXcQ --part=snippet,statistics

# Authenticated
php tools/youtube-raw-query.php subscriptions --mine=true --auth

# Save as fixture
php tools/youtube-raw-query.php videos --id=dQw4w9WgXcQ --save=example-video
```

---

## youtube-oauth-setup.php

Interactive OAuth flow to obtain a refresh token. See [Setup](#4-authorize-a-youtube-channel) above.

```bash
php tools/youtube-oauth-setup.php
```

Re-run at any time to re-authorize or switch channels.
