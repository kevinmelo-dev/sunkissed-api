<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http;

use Illuminate\Http\JsonResponse;

/**
 * Builds the project's standard JSON response envelope. Every endpoint returns one of
 * these shapes — controllers must not hand-roll ad-hoc JSON structures.
 *
 * Success:
 *   { "data": <payload>, "meta": <optional> }
 *
 * Error:
 *   { "error": { "code": "domain_error", "message": "…", "details": <optional> } }
 *
 * Error messages are in Brazilian Portuguese (surfaced to end users); codes are stable
 * English identifiers for the client to switch on.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function success(mixed $data, ?array $meta = null, int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return new JsonResponse($payload, $status);
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    public static function error(
        string $code,
        string $message,
        int $status = 422,
        ?array $details = null,
    ): JsonResponse {
        $error = ['code' => $code, 'message' => $message];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return new JsonResponse(['error' => $error], $status);
    }
}
