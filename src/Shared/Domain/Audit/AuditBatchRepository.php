<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * Persists and updates AuditBatch aggregates. The Eloquent implementation lives in
 * Infrastructure. `save` assigns an id on first persistence and updates thereafter.
 */
interface AuditBatchRepository
{
    public function save(AuditBatch $batch): void;

    public function findById(int $id): ?AuditBatch;
}
