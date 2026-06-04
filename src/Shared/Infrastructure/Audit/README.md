# Audit & observability

Business-significant actions are recorded through a single domain interface,
`AuditLogger`, and land in two places: a **queryable table** for fast operational
lookups, and a **durable JSON Lines archive** on S3-compatible storage (AWS S3 or
Cloudflare R2) for cheap, long-term retention. Bulk operations are additionally tracked
as **batches**. See [ADR-0008](../../../../docs/adr/0008-audit-logging-table-plus-archived-file.md).

## Why two stores

- The **table** (`audit_logs`) answers "what happened to order 1042?" instantly,
  indexed by subject and time. It is the operational view (and what the admin
  observability screen reads).
- The **archive** (JSON Lines on object storage) is the append-only, tamper-resistant
  record for retention and export. It is cheap and never queried hot.

## Two ways to record

```php
// 1) A single, pointwise event — e.g. an order state transition.
//    Written to the table now; archived asynchronously via a queued job so the
//    request is never blocked by object storage.
$audit->log(new AuditEvent(
    action: 'order.status_changed',
    actor: AuditActor::system('webhook'),
    subject: 'order:1042',
    context: ['from' => 'aguardando_pagamento', 'to' => 'pago'],
));

// 2) A bulk operation — e.g. a mass stock adjustment. Events are grouped under one
//    tracked batch and archived together as a single file. Progress and status are
//    persisted; an exception inside marks the batch failed and is re-thrown.
$batch = $audit->batch(
    AuditBatchContext::STOCK_BULK_ADJUSTMENT,
    'Ajuste de estoque em massa',
    total: count($variants),
    work: function (BatchRecorder $r) use ($variants): void {
        foreach ($variants as $v) {
            // ... do the work ...
            $r->record('stock.adjusted', AuditActor::admin($adminId), "variant:{$v->id}", ['delta' => $v->delta]);
            $r->itemDone();
        }
    },
);
// $batch->archivePath() now points at the stored log; $batch->status() is the outcome.
```

## Design notes (what differs from a naive logger)

- **The domain depends only on interfaces** (`AuditLogger`, `AuditArchive`,
  `AuditLogRepository`, `AuditBatchRepository`). Storage, queue, and Eloquent live in
  infrastructure and are bound in `SharedServiceProvider`. The logger is fully
  unit-testable with in-memory fakes (see `tests/Unit/Shared/Audit`).
- **UTF-8 throughout.** The archive writes JSON with `JSON_UNESCAPED_UNICODE`; nothing
  is converted to a legacy charset, so accents and emoji are preserved and files open
  correctly in any modern tool.
- **Archiving never blocks a request.** Pointwise events are archived on the queue;
  only batches (already background work) archive synchronously, because the batch row
  must record where its log landed.
- **The batch is a guarded state machine** (`pending → running → completed_*`), not a
  set of mutable fields poked from outside — invalid transitions throw.
- **Actors and subjects are namespaced strings** (`admin:3`, `order:1042`) so any
  context can record without coupling to another's models.

## Layout

```
Domain/Audit/         AuditLogger, AuditEvent, AuditActor, AuditSeverity,
                      AuditBatch, BatchRecorder, AuditBatchStatus/Context,
                      and the repository/archive interfaces.
Infrastructure/Audit/ DefaultAuditLogger, S3AuditArchive, AuditArchiveDispatcher,
                      Eloquent models + repositories, ArchiveAuditEvents job.
```
