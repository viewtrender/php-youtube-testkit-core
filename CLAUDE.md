# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP test-double library for the Google YouTube Data API v3. It intercepts HTTP requests at the Guzzle layer so tests can exercise `Google\Service\YouTube` service objects without hitting real API endpoints. Namespace: `Viewtrender\Youtube`.

## Commands

```bash
composer install                          # install dependencies (no vendor dir yet)
./vendor/bin/phpunit                      # run all tests
./vendor/bin/phpunit tests/Unit           # run unit tests only
./vendor/bin/phpunit tests/Integration    # run integration tests only
./vendor/bin/phpunit --filter=DataApiTest # run a single test class
./vendor/bin/phpunit --filter=test_list_videos_returns_video_list_response  # run a single test method
```

PHPUnit config lives in `phpunit.xml.dist`. No linter, static analysis, or CI pipeline is configured yet.

## Architecture

### Faking flow

`YoutubeDataApi::fake($responses)` is the main entry point. It creates a `YoutubeClient` which:
1. Builds a Guzzle `MockHandler` and pushes queued responses onto it
2. Attaches Guzzle's `Middleware::history()` to capture every request into `RequestHistory` (via `&getContainer()` by-reference binding)
3. Wires the mock Guzzle client into a `GoogleClient` with a fake access token
4. Returns the `YoutubeClient` instance (also stored as a static singleton on `YoutubeDataApi`)

Test code then calls `YoutubeDataApi::client()` to get the configured `GoogleClient` and passes it to `new YouTube(...)` — all subsequent YouTube API calls are served from the mock queue.

### Factories (`src/Factories/`)

Each factory (YoutubeVideo, YoutubeChannel, YoutubePlaylist, YoutubeSearchResult) follows the same pattern:
- `::list($overrides)` — full API list response from fixture, with top-level overrides
- `::listWith{Resource}s($items)` — list response with custom items (deep-merged from fixture base)
- `::empty()` — valid list response with zero items
- `::{resource}($overrides)` — single item array (not wrapped in a response)

Factories load JSON fixtures from `src/Fixtures/youtube/` and use a custom `mergeRecursive` that respects indexed arrays (replaces lists rather than merging by index).

### Responses (`src/Responses/`)

- `FakeResponse` — fluent builder that produces PSR-7 responses. Can be created from arrays, raw JSON strings, or fixture files via `::fromFixture()`.
- `ErrorResponse` — static helpers for common YouTube error shapes (notFound, forbidden, unauthorized, quotaExceeded, badRequest).

### Assertions

`RequestHistory` provides PHPUnit assertions: `assertSent`, `assertNotSent`, `assertNothingSent`, `assertSentCount`, `assertCalledPath`, and endpoint-specific shortcuts (`assertListedVideos`, `assertSearched`, `assertListedChannels`, `assertListedPlaylists`). These are also available as static proxies on `YoutubeDataApi`.

### Container swap hook

`YoutubeDataApi::registerContainerSwap(Closure)` allows framework integrations (e.g. Laravel) to register a callback that runs when `fake()` is called, enabling service container rebinding.

## Conventions

- PHP 8.3+, strict types on every file
- All classes use static factory methods; constructors are private or internal
- Tests extend `Viewtrender\Youtube\Tests\TestCase` which calls `YoutubeDataApi::reset()` in `tearDown`
- Test method names use `test_snake_case` style
- This is a library — `composer.lock` is gitignored; run `composer update` (not `install`) when starting fresh
- New factories should mirror the existing pattern: `list`, `listWith{Resource}s`, `empty`, `{resource}`, with private `build{Resource}`, `loadFixture`, and `mergeRecursive` helpers
