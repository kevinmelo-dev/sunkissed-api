<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit;

use Src\Shared\Domain\Audit\AuditArchive;

/**
 * Small infrastructure wrapper used by the batch flow to archive synchronously and get
 * the resulting path back (the batch row must store where its log landed). Pointwise
 * events take the asynchronous path via the ArchiveAuditEvents job instead; this
 * wrapper keeps that distinction explicit at the call site rather than overloading the
 * AuditArchive interface with sync/async variants.
 */
final readonly class AuditArchiveDispatcher
{
    public function __construct(
        private AuditArchive $archive,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $payload  canonical event arrays
     */
    public function archiveNow(string $key, array $payload): string
    {
        return $this->archive->append($key, $payload);
    }
}
