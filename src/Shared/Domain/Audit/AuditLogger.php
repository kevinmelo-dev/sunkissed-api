<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * The entry point business code uses to record audit events. Business code depends on
 * this interface only — never on Storage, never on Log, never on Eloquent. See
 * ADR-0008.
 *
 * Two modes:
 *  - log(): record a single, pointwise event (e.g. an order state transition). It is
 *    persisted to the queryable table immediately and archived asynchronously.
 *  - batch(): run a bulk operation; events recorded inside the callback are grouped
 *    under one AuditBatch, buffered, and archived together as one file on completion.
 */
interface AuditLogger
{
    public function log(AuditEvent $event): void;

    /**
     * Run a bulk operation under a tracked batch. The callback receives a
     * BatchRecorder it can use to record events and report progress. The batch's
     * lifecycle (start, complete/fail, archive) is managed automatically; an
     * exception thrown inside marks the batch as failed and is re-thrown.
     *
     * @param  callable(BatchRecorder $recorder): void  $work
     * @return AuditBatch the completed batch (with id, status, archive path)
     */
    public function batch(
        AuditBatchContext $context,
        string $description,
        int $total,
        callable $work,
    ): AuditBatch;
}
