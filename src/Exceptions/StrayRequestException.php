<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Exceptions;

use Psr\Http\Message\RequestInterface;

class StrayRequestException extends \RuntimeException
{
    public function __construct(RequestInterface $request)
    {
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        parent::__construct(
            "Unexpected request: {$method} {$uri}. No more fake responses in the queue. "
            . "Did you forget to queue a response with YoutubeDataApi::fake()?"
        );
    }
}
