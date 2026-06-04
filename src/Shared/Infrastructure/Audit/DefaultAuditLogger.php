<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Audit;

use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditBatchRepository;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\Audit\AuditLogRepository;
use Src\Shared\Domain\Audit\BatchRecorder;
use Src\Shared\Infrastructure\Audit\Jobs\ArchiveAuditEvents;
use Throwable;

/**
 * Default AuditLogger. Composes the queryable repository, the batch repository, and
 * the durable archive.
 *
 *  - log(): write the event to the queryable table now (fast, indexed), and push the
 *    archive write onto the queue so the request is never blocked by object storage.
 *  - batch(): manage the full batch lifecycle (open → start → run → complete),
 *    persist its events to the table, and archive them as a single file. The archive
 *    write for a batch is synchronous because the batch is already a background
 *    operation and we want its archive path recorded on completion.
 */
final readonly class DefaultAuditLogger implements AuditLogger
{
    public function __construct(
        private AuditLogRepository $logs,
        private AuditBatchRepository $batches,
        private AuditArchiveDispatcher $archive,
    ) {}

    public function log(AuditEvent $event): void
    {
        $this->logs->store($event);

        // Archive asynchronously; archiving must never slow the request down.
        ArchiveAuditEvents::dispatch(
            key: $this->datePartitionKey($event),
            payload: [$event->toArray()],
        );
    }

    public function batch(
        AuditBatchContext $context,
        string $description,
        int $total,
        callable $work,
    ): AuditBatch {
        $batch = AuditBatch::open($context, $description, $total);
        $this->batches->save($batch);   // assigns id
        $batch->start();
        $this->batches->save($batch);

        $recorder = new BatchRecorder($batch);

        try {
            $work($recorder);

            $this->finalizeBatch($recorder, success: true);

            return $batch;
        } catch (Throwable $e) {
            $this->finalizeBatch($recorder, success: false, error: $e->getMessage());

            throw $e;
        }
    }

    private function finalizeBatch(BatchRecorder $recorder, bool $success, ?string $error = null): void
    {
        $batch = $recorder->batch();
        $events = $recorder->bufferedEvents();

        $this->logs->storeMany($events);

        $path = null;
        if ($events !== []) {
            $payload = array_map(static fn (AuditEvent $e): array => $e->toArray(), $events);
            $path = $this->archive->archiveNow("batches/{$batch->requireId()}", $payload);
        }

        $batch->complete(success: $success, archivePath: $path, error: $error);
        $this->batches->save($batch);
    }

    /**
     * Single events are archived into a date partition (year/month/day) rather than a
     * batch file, so they accumulate in browsable daily files.
     */
    private function datePartitionKey(AuditEvent $event): string
    {
        return 'events/'.$event->occurredAtOrNow()->format('Y/m/d');
    }
}
