<?php

declare(strict_types=1);

namespace Viewtrender\Youtube;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

class RequestHistory
{
    /** @var array<int, array{request: RequestInterface, response: mixed}> */
    private array $container = [];

    /**
     * @return array<int, array{request: RequestInterface, response: mixed}>
     */
    public function &getContainer(): array
    {
        return $this->container;
    }

    /**
     * @return array<int, RequestInterface>
     */
    public function requests(): array
    {
        return array_map(fn (array $entry) => $entry['request'], $this->container);
    }

    public function count(): int
    {
        return count($this->container);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function assertSent(callable $callback): void
    {
        Assert::assertTrue(
            $this->hasSent($callback),
            'An expected request was not sent.',
        );
    }

    public function assertNotSent(callable $callback): void
    {
        Assert::assertFalse(
            $this->hasSent($callback),
            'An unexpected request was sent.',
        );
    }

    public function assertNothingSent(): void
    {
        Assert::assertEmpty(
            $this->container,
            sprintf('Expected no requests to be sent, but %d were sent.', $this->count()),
        );
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount(
            $count,
            $this->container,
            sprintf('Expected %d requests, but %d were sent.', $count, $this->count()),
        );
    }

    public function assertCalledPath(string $path): void
    {
        $this->assertSent(function (RequestInterface $request) use ($path): bool {
            return str_contains($request->getUri()->getPath(), $path);
        });
    }

    public function assertListedVideos(): void
    {
        $this->assertCalledPath('/youtube/v3/videos');
    }

    public function assertSearched(): void
    {
        $this->assertCalledPath('/youtube/v3/search');
    }

    public function assertListedChannels(): void
    {
        $this->assertCalledPath('/youtube/v3/channels');
    }

    public function assertListedPlaylists(): void
    {
        $this->assertCalledPath('/youtube/v3/playlists');
    }

    private function hasSent(callable $callback): bool
    {
        foreach ($this->requests() as $request) {
            if ($callback($request)) {
                return true;
            }
        }

        return false;
    }
}
