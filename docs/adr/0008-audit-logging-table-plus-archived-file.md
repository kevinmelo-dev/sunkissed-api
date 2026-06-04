# 0008. Audit logging: queryable table plus archived file

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

The system must keep an audit trail of business-significant actions (stock
adjustments, order state transitions, payment events, admin changes) for traceability
and accountability — a practice carried over from production experience. We need both
fast querying ("what happened to this order / who adjusted this stock") and cheap,
durable, tamper-resistant long-term retention. Some operations happen in **batches**
(bulk product import, mass stock changes) and should be grouped.

## Decision

We will log audit events through a single **`AuditLogger`** service in
`Shared/Infrastructure/Audit`, writing to **two layers**:

1. An **`audit_logs` table** — indexed, queryable, for recent/operational lookups
   (actor, action, subject, context, timestamp).
2. An **archived file** in JSON Lines format on **S3-compatible storage** (AWS S3 or
   Cloudflare R2, via Laravel's `s3` filesystem driver) — append-only, cheap,
   long-retention.

Batch operations receive a `batch_id` that groups their entries and produces a single
archived file per batch. Business code never calls `Log::info` for audit events; it
goes through `AuditLogger`.

## Consequences

- Fast operational queries from the table; cheap durable history in object storage.
- Storage is provider-agnostic: AWS S3 and Cloudflare R2 are both S3-compatible, so
  switching is configuration, not code.
- Batch grouping mirrors the proven approach from production and keeps mass-operation
  logs coherent.
- Two write paths to keep consistent. Pointwise events are written to the table
  synchronously (they are small and indexed) and archived **asynchronously via a
  queued job**, so a slow or briefly unavailable object store never affects API
  latency. Batches, which are already background work, archive synchronously because
  the batch row must record where its log landed.
- The archive is written as **UTF-8 JSON Lines** (`JSON_UNESCAPED_UNICODE`),
  explicitly avoiding the legacy ISO-8859-1 conversion seen in the original
  production logger, which mangled accents and broke modern tooling.
- The domain depends only on interfaces (`AuditLogger`, `AuditArchive`, the two
  repositories), making the whole subsystem unit-testable with in-memory fakes and
  keeping object storage / Eloquent out of the domain.
- Product images use the same `s3` filesystem, keeping storage configuration unified.

### Superseded approach

An earlier production version coupled the logger directly to a concrete disk, wrote in
ISO-8859-1, managed an open file handle across the request (reopening it on demand and
flushing in a destructor), and only persisted a file (relying on the `batch` table as
the sole queryable index). This redesign keeps the proven batch concept but moves to
interfaces, UTF-8, a dedicated queryable table for pointwise events, and asynchronous
archiving.
