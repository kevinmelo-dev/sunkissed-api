<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * The handle given to the callback of AuditLogger::batch(). Business code uses it to
 * record events and report progress during a bulk operation, without knowing how the
 * batch is persisted or archived.
 *
 * This replaces the production BatchManager's mutable, stateful API (create/load/
 * start/increment/finish exposed directly) with a scoped object that only does what
 * the work body needs: record an event, mark one item done, adjust the total.
 */
final class BatchRecorder
{
    /** @var array<int, AuditEvent> */
    private array $buffer = [];

    public function __construct(
        private readonly AuditBatch $batch,
    ) {}

    /**
     * Record an event within this batch. Buffered in memory and flushed (table +
     * archive) when the batch completes.
     *
     * @param  array<string, mixed>  $context
     */
    public function record(
        string $action,
        AuditActor $actor,
        string $subject,
        array $context = [],
        AuditSeverity $severity = AuditSeverity::INFO,
    ): void {
        $this->buffer[] = new AuditEvent(
            action: $action,
            actor: $actor,
            subject: $subject,
            context: $context,
            severity: $severity,
            batchId: $this->batch->id(),
        );
    }

    /**
     * Mark progress: one (or more) items finished.
     */
    public function itemDone(int $count = 1): void
    {
        $this->batch->increment($count);
    }

    public function adjustTotal(int $total): void
    {
        $this->batch->adjustTotal($total);
    }

    public function batch(): AuditBatch
    {
        return $this->batch;
    }

    /**
     * @return array<int, AuditEvent>
     */
    public function bufferedEvents(): array
    {
        return $this->buffer;
    }
}
