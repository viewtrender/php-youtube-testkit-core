<?php

declare(strict_types=1);

namespace Viewtrender\Youtube;

use Closure;
use Google\Client as GoogleClient;
use Google\Service\YouTubeReporting;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeReportingApi
{
    private static ?YoutubeReportingClient $instance = null;

    private static ?Closure $containerSwap = null;

    public static function registerContainerSwap(Closure $callback): void
    {
        static::$containerSwap = $callback;
    }

    /**
     * @param  array<ResponseInterface|FakeResponse>  $responses
     */
    public static function fake(array $responses = []): YoutubeReportingClient
    {
        static::$instance = new YoutubeReportingClient($responses);

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

    public static function reporting(): YouTubeReporting
    {
        return new YouTubeReporting(static::client());
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

    public static function assertCalledPath(string $path): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertCalledPath($path);
    }

    public static function assertListedReportTypes(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertCalledPath('youtubereporting/v1/reportTypes');
    }

    public static function assertCreatedJob(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertCalledPath('youtubereporting/v1/jobs');
    }

    public static function assertListedReports(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertCalledPath('youtubereporting/v1/jobs/*/reports');
    }

    public static function reset(): void
    {
        static::$instance = null;
    }

    public static function instance(): ?YoutubeReportingClient
    {
        return static::$instance;
    }

    private static function ensureInitialized(): void
    {
        if (static::$instance === null) {
            throw new RuntimeException(
                'YoutubeReportingApi has not been initialized. Call YoutubeReportingApi::fake() first.'
            );
        }
    }
}