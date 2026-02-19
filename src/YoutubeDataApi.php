<?php

declare(strict_types=1);

namespace Viewtrender\Youtube;

use Closure;
use Google\Client as GoogleClient;
use Google\Service\YouTube;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Viewtrender\Youtube\Responses\FakeResponse;

class YoutubeDataApi
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

    public static function youtube(): YouTube
    {
        return new YouTube(static::client());
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

    public static function assertListedVideos(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertListedVideos();
    }

    public static function assertSearched(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertSearched();
    }

    public static function assertListedChannels(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertListedChannels();
    }

    public static function assertListedPlaylists(): void
    {
        static::ensureInitialized();
        static::$instance->getRequestHistory()->assertListedPlaylists();
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
                'YoutubeDataApi has not been initialized. Call YoutubeDataApi::fake() first.'
            );
        }
    }
}
