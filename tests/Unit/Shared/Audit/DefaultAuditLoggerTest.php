<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Src\Shared\Domain\Audit\AuditActor;
use Src\Shared\Domain\Audit\AuditArchive;
use Src\Shared\Domain\Audit\AuditBatch;
use Src\Shared\Domain\Audit\AuditBatchContext;
use Src\Shared\Domain\Audit\AuditBatchRepository;
use Src\Shared\Domain\Audit\AuditBatchStatus;
use Src\Shared\Domain\Audit\AuditEvent;
use Src\Shared\Domain\Audit\AuditLogRepository;
use Src\Shared\Domain\Audit\BatchRecorder;
use Src\Shared\Infrastructure\Audit\AuditArchiveDispatcher;
use Src\Shared\Infrastructure\Audit\DefaultAuditLogger;
use Src\Shared\Infrastructure\Audit\Jobs\ArchiveAuditEvents;

/**
 * In-memory fakes so the logic is tested without a DB or S3.
 */
function fakeLogRepo(): AuditLogRepository
{
    return new class implements AuditLogRepository
    {
        /** @var array<int, AuditEvent> */
        public array $stored = [];

        public function store(AuditEvent $event): void
        {
            $this->stored[] = $event;
        }

        public function storeMany(array $events): void
        {
            foreach ($events as $e) {
                $this->stored[] = $e;
            }
        }
    };
}

function fakeBatchRepo(): AuditBatchRepository
{
    return new class implements AuditBatchRepository
    {
        public int $saveCount = 0;

        private int $nextId = 1;

        public function save(AuditBatch $batch): void
        {
            $this->saveCount++;
            if ($batch->id() === null) {
                $batch->assignId($this->nextId++);
            }
        }

        public function findById(int $id): ?AuditBatch
        {
            return null;
        }
    };
}

function fakeArchiveDispatcher(): AuditArchiveDispatcher
{
    $archive = new class implements AuditArchive
    {
        /** @var array<string, array<int, array<string, mixed>>> */
        public array $written = [];

        public function append(string $key, array $events): string
        {
            $this->written[$key] = array_merge($this->written[$key] ?? [], $events);

            return "audit-logs/{$key}.log";
        }
    };

    return new AuditArchiveDispatcher($archive);
}

it('stores a single event in the table and dispatches async archiving', function (): void {
    Queue::fake();

    $logs = fakeLogRepo();
    $logger = new DefaultAuditLogger($logs, fakeBatchRepo(), fakeArchiveDispatcher());

    $logger->log(new AuditEvent(
        action: 'order.status_changed',
        actor: AuditActor::system('webhook'),
        subject: 'order:1042',
        context: ['to' => 'pago'],
    ));

    expect($logs->stored)->toHaveCount(1);
    Queue::assertPushed(ArchiveAuditEvents::class, 1);
});

it('runs a batch end to end: lifecycle, events, sync archive', function (): void {
    $logs = fakeLogRepo();
    $batchRepo = fakeBatchRepo();
    $logger = new DefaultAuditLogger($logs, $batchRepo, fakeArchiveDispatcher());

    $result = $logger->batch(
        AuditBatchContext::STOCK_BULK_ADJUSTMENT,
        'Ajuste em massa',
        2,
        function (BatchRecorder $r): void {
            $r->record('stock.adjusted', AuditActor::admin(1), 'variant:10', ['delta' => 5]);
            $r->itemDone();
            $r->record('stock.adjusted', AuditActor::admin(1), 'variant:11', ['delta' => -2]);
            $r->itemDone();
        }
    );

    expect($result->status())->toBe(AuditBatchStatus::COMPLETED_SUCCESS)
        ->and($result->finished())->toBe(2)
        ->and($result->archivePath())->toBe('audit-logs/batches/1.log')
        ->and($logs->stored)->toHaveCount(2);
});

it('marks a batch as failed and rethrows when the work throws', function (): void {
    $logs = fakeLogRepo();
    $logger = new DefaultAuditLogger($logs, fakeBatchRepo(), fakeArchiveDispatcher());

    $run = fn () => $logger->batch(
        AuditBatchContext::ORDER_IMPORT,
        'Importação',
        1,
        function (BatchRecorder $r): void {
            $r->record('order.imported', AuditActor::system('import'), 'order:1');
            throw new RuntimeException('boom');
        }
    );

    expect($run)->toThrow(RuntimeException::class, 'boom');
    // The buffered event is still persisted to the table for forensics.
    expect($logs->stored)->toHaveCount(1);
});
