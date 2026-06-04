<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Shared\Domain\Audit\AuditArchive;

/**
 * Archives pointwise audit events to durable storage off the request path. The event
 * is already in the queryable table by the time this runs; this job only handles the
 * slow object-storage write, so a slow or briefly unavailable S3/R2 never affects API
 * latency. Carries the canonical array payload (AuditEvent::toArray()) to keep the
 * queued job small and serialization-safe.
 */
final class ArchiveAuditEvents implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $backoff = 10;

    /**
     * @param  array<int, array<string, mixed>>  $payload  canonical event arrays
     */
    public function __construct(
        public string $key,
        public array $payload,
    ) {}

    public function handle(AuditArchive $archive): void
    {
        $archive->append($this->key, $this->payload);
    }
}
