<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * Writes audit events to durable, append-only archive storage (S3-compatible:
 * AWS S3 or Cloudflare R2) as JSON Lines, UTF-8. The path layout and disk are an
 * infrastructure concern; the domain only needs "archive these events and tell me
 * where they landed".
 *
 * Events are passed in their canonical array form (AuditEvent::toArray()) rather than
 * as objects, so the same contract works whether the caller is in-process (a batch
 * flushing its buffer) or a queued job carrying a serialized payload.
 */
interface AuditArchive
{
    /**
     * Append events to the archive for a given logical key (e.g. a batch id or a
     * date partition) and return the stored path.
     *
     * @param  array<int, array<string, mixed>>  $events  canonical event arrays
     */
    public function append(string $key, array $events): string;
}
