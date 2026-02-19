<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Responses;

class ErrorResponse
{
    public static function notFound(string $message = 'The resource identified by the request could not be found.'): FakeResponse
    {
        return self::build(404, 'NOT_FOUND', $message);
    }

    public static function forbidden(string $message = 'The caller does not have permission.'): FakeResponse
    {
        return self::build(403, 'FORBIDDEN', $message);
    }

    public static function unauthorized(string $message = 'The request does not have valid authentication credentials.'): FakeResponse
    {
        return self::build(401, 'UNAUTHORIZED', $message);
    }

    public static function quotaExceeded(string $message = 'The request cannot be completed because you have exceeded your quota.'): FakeResponse
    {
        return self::build(403, 'QUOTA_EXCEEDED', $message, 'youtube.quota', 'rateLimitExceeded');
    }

    public static function badRequest(string $message = 'The API server failed to successfully process the request.'): FakeResponse
    {
        return self::build(400, 'BAD_REQUEST', $message);
    }

    private static function build(
        int $httpStatus,
        string $status,
        string $message,
        ?string $domain = 'youtube.api',
        ?string $reason = null,
    ): FakeResponse {
        $error = [
            'domain' => $domain ?? 'youtube.api',
            'reason' => $reason ?? strtolower($status),
            'message' => $message,
        ];

        return FakeResponse::make([
            'error' => [
                'code' => $httpStatus,
                'message' => $message,
                'errors' => [$error],
                'status' => $status,
            ],
        ])->status($httpStatus);
    }
}
