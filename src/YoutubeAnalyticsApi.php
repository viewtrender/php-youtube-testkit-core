<?php

declare(strict_types=1);

namespace Viewtrender\Youtube;

use Closure;
use Google\Client as GoogleClient;
use Google\Service\YouTubeAnalytics;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeAnalyticsApi
{
    private static ?YoutubeClient $instance = null;

    private static ?Closure $containerSwap = null;

    public static function registerContainerSwap(Closure $callback): void
    {
        static::$containerSwap = $callback;
    }

    /**
     * @param  array<ResponseInterface|FakeResponse>  $responses
     */
    public static function fake(array $responses = []): YoutubeClient
    {
        static::$instance = new YoutubeClient($responses);

        if (static::$containerSwap !== null) {
            (static::$containerSwap)();
        }

        return static::$instance;
    }

    public static function client(): GoogleClient
    {
        static::ensureInitialized();

        return static::$instance->getGoogleClient();
    }

    public static function analytics(): YouTubeAnalytics
    {
        return new YouTubeAnalytics(static::client());
    }

    public static function assertSent(callable $callback): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertSent($callback);
    }

    public static function assertNotSent(callable $callback): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertNotSent($callback);
    }

    public static function assertNothingSent(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertNothingSent();
    }

    public static function assertSentCount(int $count): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertSentCount($count);
    }

    public static function assertQueriedAnalytics(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertSent(function ($request) {
            return str_contains($request->getUri()->getPath(), '/v2/reports');
        });
    }

    public static function assertQueriedMetrics(string $metrics): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertSent(function ($request) use ($metrics) {
            parse_str($request->getUri()->getQuery(), $query);
            return isset($query['metrics']) && str_contains($query['metrics'], $metrics);
        });
    }

    public static function assertQueriedDimensions(string $dimensions): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertSent(function ($request) use ($dimensions) {
            parse_str($request->getUri()->getQuery(), $query);
            return isset($query['dimensions']) && str_contains($query['dimensions'], $dimensions);
        });
    }

    public static function reset(): void
    {
        static::$instance = null;
    }

    public static function instance(): ?YoutubeClient
    {
        return static::$instance;
    }

    private static function ensureInitialized(): void
    {
        if (static::$instance === null) {
            throw new RuntimeException(
                'YoutubeAnalyticsApi has not been initialized. Call YoutubeAnalyticsApi::fake() first.'
            );
        }
    }
}