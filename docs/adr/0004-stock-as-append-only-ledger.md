# 0004. Stock as an append-only movement ledger

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

The store currently tracks stock with handwritten notes and has no reliable picture
of what is available. Stock needs to be correct under concurrent operations (two
buyers, a reservation during checkout, a manual adjustment by the owner) and
auditable (why did the count change?). A single mutable `quantity` column loses
history and is fragile under concurrency.

## Decision

We will model stock as an **append-only ledger**: an `inventory_movements` table
where each row is a typed movement (`entrada`, `saida`, `reserva`, `liberacao`,
`ajuste`) against a variant, with a quantity, a reason, and a reference (e.g. an
order id) plus a timestamp. **Current available stock is derived** by summing the
ledger; it is never stored as the source of truth.

A cached/materialized balance per variant may be maintained for performance, but it
is always reconstructable from the ledger and is never authoritative.

## Consequences

- Full history and auditability: every change has a type, reason, and reference.
- Reservations are first-class (a movement type), which the checkout/payment flow
  needs (see ADR-0005).
- Reads of "current stock" are an aggregation. At this store's volume a direct sum is
  fine; if it ever isn't, a cached balance recomputed from the ledger is the escape
  hatch.
- Discipline required: nothing may mutate stock except by appending a movement.
